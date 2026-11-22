<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\RoyaltyPayment;
use App\Services\Banking\BankStatementException;
use App\Services\Royalties\RoyaltyPayments;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Rebeat-Zahlungen: the payouts a statement distributes. Taken from the bank
 * imports on their own; years without an accounting in the tool come from an
 * uploaded bank statement (only the Rebeat lines are kept) or by hand.
 */
class RoyaltyPaymentController extends Controller
{
    public function index(RoyaltyPayments $payments)
    {
        $new = $payments->collectFromBank();

        $all = RoyaltyPayment::with('statement')->orderByDesc('paid_at')->get();

        return view('admin.royalties.payments', [
            'payments' => $all,
            'years' => $all->groupBy(fn ($p) => $p->paid_at->year)->map(fn ($p) => [
                'count' => $p->count(), 'amount' => (float) $p->sum('amount'), 'foreign' => (float) $p->sum('amount_foreign'),
            ])->sortKeys(),
            'newFromBank' => $new,
        ]);
    }

    public function upload(Request $request, RoyaltyPayments $payments)
    {
        $request->validate(['files' => 'required|array', 'files.*' => 'file|max:10240'], ['files.required' => 'Bitte mindestens einen Kontoauszug (CSV) wählen.']);

        $found = 0;
        $new = 0;
        foreach ($request->file('files') as $file) {
            try {
                $result = $payments->collectFromFile($file->getRealPath());
            } catch (BankStatementException $e) {
                return back()->with('error', $file->getClientOriginalName() . ': ' . $e->getMessage());
            }
            $found += $result['found'];
            $new += $result['new'];
        }

        if ($found === 0) {
            return back()->with('error', 'In den Dateien steht keine Zahlung von Rebeat.');
        }

        return back()->with('success', "{$found} Rebeat-Zahlungen gefunden, davon {$new} neu.");
    }

    public function store(Request $request, RoyaltyPayments $payments)
    {
        $data = $request->validate([
            'paid_at' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'amount_foreign' => 'nullable|numeric|min:0.01',
            'text' => 'nullable|string|max:255',
        ]);

        $payment = $payments->addManually(Carbon::parse($data['paid_at']), (float) $data['amount'],
            isset($data['amount_foreign']) ? (float) $data['amount_foreign'] : null, $data['text'] ?? 'Von Hand erfasst');

        return back()->with($payment ? 'success' : 'error', $payment ? 'Zahlung erfasst.' : 'Diese Zahlung gibt es schon.');
    }

    public function destroy(RoyaltyPayment $royaltyPayment)
    {
        if ($royaltyPayment->statement?->is_final) {
            return back()->with('error', 'Diese Zahlung ist in einer abgeschlossenen Abrechnung verteilt.');
        }
        if ($royaltyPayment->origin === 'bank') {
            return back()->with('error', 'Diese Zahlung kommt aus dem Bankimport einer Buchhaltung und wird dort verwaltet.');
        }
        $royaltyPayment->delete();

        return back()->with('success', 'Zahlung entfernt.');
    }
}
