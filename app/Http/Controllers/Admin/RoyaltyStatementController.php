<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Organization;
use App\Models\RoyaltyLine;
use App\Models\RoyaltyStatement;
use App\Models\RoyaltyStatementItem;
use App\Services\Royalties\RoyaltyPayments;
use App\Services\Royalties\RoyaltySettings;
use App\Services\Royalties\RoyaltyStatementBooker;
use App\Services\Royalties\RoyaltyStatementCalculator;
use App\Services\Royalties\RoyaltyStatementException;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

/**
 * Abrechnungen an Künstler:innen und Labels: a draft is computed live (so
 * contracts added meanwhile count), finalizing books it, undoing takes it
 * back as long as nothing of it is paid out.
 */
class RoyaltyStatementController extends Controller
{
    public function index()
    {
        $statements = RoyaltyStatement::with('accounting')->orderByDesc('statement_date')->orderByDesc('id')->get();
        $today = now('Europe/Zurich');
        $date = Carbon::create($today->year, 12, 15);
        $firstOpen = RoyaltyLine::whereNull('royalty_statement_id')->where('presettled', false)->min('period');
        $from = $firstOpen ? Carbon::parse($firstOpen)->startOfMonth() : Carbon::create($date->year - 1, 1, 1);
        $to = Carbon::create($date->year - 1, 12, 31);

        return view('admin.royalties.statements', [
            'statements' => $statements,
            'accountings' => Accounting::with('accountable')->orderByDesc('period_start')->get(),
            'defaults' => [
                'title' => 'Abrechnung ' . ($from->year === $to->year ? $to->year : "{$from->year}–{$to->year}"),
                'statement_date' => $date->toDateString(),
                'period_from' => $from->toDateString(),
                'period_to' => $to->toDateString(),
                'accounting_id' => $this->accountingFor($date)?->id,
            ],
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $statement = RoyaltyStatement::create($data + [
            'status' => RoyaltyStatement::DRAFT,
            'accounts' => RoyaltyStatementBooker::defaultAccounts(Accounting::find($data['accounting_id'] ?? null)),
        ]);

        return redirect()->route('admin.royalties.statements.show', $statement)->with('success', 'Entwurf erstellt – prüfe die Vorschau.');
    }

    public function show(RoyaltyStatement $royaltyStatement, RoyaltyStatementCalculator $calculator, RoyaltyPayments $payments)
    {
        $statement = $royaltyStatement->load('accounting');

        if ($statement->is_final) {
            $items = $statement->items()->with(['track', 'release', 'contract'])->get();
            $result = [
                'payments' => $statement->payments()->orderBy('paid_at')->get(),
                'pot' => (float) $statement->payments_amount,
                'usage' => (float) $statement->usage_amount,
                'factor' => (float) $statement->factor,
                'items' => $items,
                'warnings' => [],
                'unconfirmed' => 0,
            ];
            $bookings = Booking::with(['debitAccount', 'creditAccount'])->whereIn('id', $statement->booking_ids ?? [])->get();
        } else {
            $payments->collectFromBank();
            $result = $calculator->calculate($statement);
            $result['items']->load(['track', 'release', 'contract']);
            $bookings = collect();
        }

        $parties = RoyaltyStatementBooker::parties($result['items']);
        $labels = $parties->where('role', RoyaltyStatementItem::LABEL)->pluck('organization')->filter();

        return view('admin.royalties.statement', [
            'statement' => $statement,
            'result' => $result,
            'parties' => $parties,
            'labels' => $labels,
            'bookings' => $bookings,
            'accountings' => Accounting::with('accountable')->orderByDesc('period_start')->get(),
            'accountOptions' => $statement->accounting?->accounts()->where('is_header', false)->orderBy('number')->get() ?? collect(),
            'accounts' => ($statement->accounts ?? []) + RoyaltyStatementBooker::defaultAccounts($statement->accounting),
        ]);
    }

    public function update(Request $request, RoyaltyStatement $royaltyStatement)
    {
        if ($royaltyStatement->is_final) {
            return back()->with('error', 'Eine abgeschlossene Abrechnung zuerst rückgängig machen.');
        }

        $data = $this->validated($request);
        $accounts = $request->validate([
            'accounts.expense' => 'nullable|integer',
            'accounts.payable' => 'nullable|integer',
            'accounts.recouped' => 'nullable|integer',
            'accounts.labels' => 'nullable|array',
            'accounts.labels.*' => 'nullable|integer',
        ])['accounts'] ?? [];

        // Another accounting: its own accounts
        if ((int) ($data['accounting_id'] ?? 0) !== (int) $royaltyStatement->accounting_id) {
            $accounts = RoyaltyStatementBooker::defaultAccounts(Accounting::find($data['accounting_id'] ?? null));
        }

        $royaltyStatement->update($data + ['accounts' => $accounts + ['labels' => []]]);

        return back()->with('success', 'Gespeichert.');
    }

    /** The statement of one party (org-ID or contact-ID) as PDF */
    public function pdf(RoyaltyStatement $royaltyStatement, string $party, \App\Services\Royalties\RoyaltyStatementDocument $document)
    {
        @set_time_limit(120);
        ['result' => $result, 'parties' => $parties] = $document->prepare($royaltyStatement->load('accounting'));
        $match = $parties->firstWhere('key', $party) ?? abort(404);

        return $document->pdf($royaltyStatement, $match, $result)->stream($document->fileName($royaltyStatement, $match));
    }

    /** All statements of the parties in one ZIP */
    public function pdfs(RoyaltyStatement $royaltyStatement, \App\Services\Royalties\RoyaltyStatementDocument $document)
    {
        @set_time_limit(300);
        @ini_set('memory_limit', '512M');
        $path = $document->zip($royaltyStatement->load('accounting'));
        $name = 'Abrechnungen_' . $royaltyStatement->period_from->format('Y') . '-' . $royaltyStatement->period_to->format('Y')
            . ($royaltyStatement->is_final ? '' : '_ENTWURF') . '.zip';

        return response()->download($path, $name)->deleteFileAfterSend();
    }

    public function finalize(RoyaltyStatement $royaltyStatement, RoyaltyStatementBooker $booker)
    {
        try {
            $booker->finalize($royaltyStatement);
        } catch (RoyaltyStatementException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Abrechnung abgeschlossen und gebucht.');
    }

    public function undo(RoyaltyStatement $royaltyStatement, RoyaltyStatementBooker $booker)
    {
        try {
            $booker->undo($royaltyStatement);
        } catch (RoyaltyStatementException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Abrechnung rückgängig gemacht: Buchungen und Gutschriften sind gelöscht, die Nutzung ist wieder offen.');
    }

    public function destroy(RoyaltyStatement $royaltyStatement)
    {
        if ($royaltyStatement->is_final) {
            return back()->with('error', 'Eine abgeschlossene Abrechnung zuerst rückgängig machen.');
        }
        $royaltyStatement->delete();

        return redirect()->route('admin.royalties.statements.index')->with('success', 'Entwurf gelöscht.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'statement_date' => 'required|date',
            'period_from' => 'required|date',
            'period_to' => 'required|date|after_or_equal:period_from',
            'accounting_id' => 'nullable|integer|exists:accountings,id',
            'pre_booked' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string|max:2000',
        ], [
            'period_to.after_or_equal' => 'Das Ende des Zeitraums liegt vor dem Anfang.',
        ]);
        $data['pre_booked'] = round((float) ($data['pre_booked'] ?? 0), 2);

        return $data;
    }

    /** The accounting the statement date falls into - of an own label if there are several */
    private function accountingFor(Carbon $date): ?Accounting
    {
        $own = RoyaltySettings::ownOrganizations()->pluck('id');

        return Accounting::with('accountable')->whereDate('period_start', '<=', $date)->whereDate('period_end', '>=', $date)->get()
            ->sortBy(fn (Accounting $a) => $a->accountable instanceof Organization && $own->contains($a->accountable->id) ? 0 : 1)
            ->first();
    }
}
