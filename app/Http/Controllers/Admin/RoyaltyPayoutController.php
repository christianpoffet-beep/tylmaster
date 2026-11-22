<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accounting;
use App\Models\RoyaltyPayout;
use App\Services\Royalties\RoyaltyBalances;
use App\Services\Royalties\RoyaltyPayouts;
use App\Services\Royalties\RoyaltyStatementException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Saldi & Auszahlungen: the balances of artists and labels, and payouts from
 * an accounting - suggested from CHF 100 for those with a valid IBAN, any
 * other amount by hand - with the pain.001 file for the e-banking.
 */
class RoyaltyPayoutController extends Controller
{
    public function index(RoyaltyBalances $balances)
    {
        $today = now('Europe/Zurich')->startOfDay();

        return view('admin.royalties.payouts', [
            'parties' => $balances->all(),
            'payouts' => RoyaltyPayout::with('accounting')->withCount('items')->latest()->limit(30)->get(),
            'accountings' => Accounting::orderByDesc('period_start')->get(),
            'currentAccounting' => Accounting::whereDate('period_start', '<=', $today)->whereDate('period_end', '>=', $today)->orderByDesc('period_start')->first(),
            'minimum' => (float) config('royalties.payout_minimum', 100),
        ]);
    }

    public function create(Request $request, RoyaltyBalances $balances)
    {
        $accounting = Accounting::with('accountable')->findOrFail($request->integer('accounting'));
        $accounts = $accounting->accounts()->where('is_header', false)->orderBy('number')->get();
        $minimum = (float) config('royalties.payout_minimum', 100);
        $today = now('Europe/Zurich')->startOfDay();
        $date = $today->betweenIncluded($accounting->period_start, $accounting->period_end) ? $today->copy()->addWeekday() : $accounting->period_end->copy();

        return view('admin.royalties.payout-create', [
            'accounting' => $accounting,
            'parties' => $balances->all()->filter(fn ($p) => $p->balance > 0)->values(),
            'bankAccounts' => $accounts->where('type', 'asset')->values(),
            'payableAccounts' => $accounts->where('type', 'liability')->values(),
            'defaultBank' => $accounts->where('type', 'asset')->first(fn ($a) => $a->iban) ?? $accounts->firstWhere('number', '1003'),
            'defaultPayable' => $accounts->firstWhere('number', '2001'),
            'minimum' => $minimum,
            'date' => $date->toDateString(),
        ]);
    }

    public function store(Request $request, RoyaltyPayouts $payouts)
    {
        $data = $request->validate([
            'accounting_id' => 'required|integer|exists:accountings,id',
            'execution_date' => 'required|date',
            'bank_account_id' => 'required|integer|exists:accounts,id',
            'payable_account_id' => 'required|integer|exists:accounts,id',
            'message' => 'nullable|string|max:140',
            'pay' => 'nullable|array',
            'amount' => 'nullable|array',
            'amount.*' => 'nullable|numeric|min:0',
        ]);

        $accounting = Accounting::findOrFail($data['accounting_id']);
        $amounts = collect($data['pay'] ?? [])->keys()->mapWithKeys(fn ($key) => [$key => (float) ($data['amount'][$key] ?? 0)])->all();

        try {
            $payout = $payouts->create($accounting, $amounts, Carbon::parse($data['execution_date']),
                $accounting->accounts()->findOrFail($data['bank_account_id']), $accounting->accounts()->findOrFail($data['payable_account_id']),
                $data['message'] ?? null);
        } catch (RoyaltyStatementException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.royalties.payouts.show', $payout)->with('success', 'Auszahlung gebucht. Jetzt die Zahlungsdatei herunterladen und im E-Banking hochladen.');
    }

    public function show(RoyaltyPayout $royaltyPayout)
    {
        $royaltyPayout->load(['accounting', 'bankAccount', 'payableAccount', 'items.booking']);

        return view('admin.royalties.payout', ['payout' => $royaltyPayout]);
    }

    public function pain001(RoyaltyPayout $royaltyPayout, RoyaltyPayouts $payouts)
    {
        $name = 'Auszahlung_' . $royaltyPayout->execution_date->format('Y-m-d') . '_' . $royaltyPayout->id . '.xml';

        return response($payouts->pain001($royaltyPayout), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $name . '"',
            'Cache-Control' => 'no-store, private',
        ]);
    }

    public function destroy(RoyaltyPayout $royaltyPayout, RoyaltyPayouts $payouts)
    {
        try {
            $payouts->cancel($royaltyPayout);
        } catch (RoyaltyStatementException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('admin.royalties.payouts.index')->with('success', 'Auszahlung storniert: Buchungen gelöscht, die Saldi sind wieder wie vorher.');
    }
}
