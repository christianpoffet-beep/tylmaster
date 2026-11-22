<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounting;
use App\Models\ChartTemplate;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Project;
use App\Services\CarryForward;
use App\Services\ChartGroups;
use Illuminate\Http\Request;

class AccountingController extends Controller
{
    public function index(Request $request)
    {
        $query = Accounting::with('accountable');

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        if ($status = $request->input('status')) {
            $query->where('status', $status);
        }

        $sortField = $request->input('sort', 'created_at');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['name', 'period_start', 'status', 'currency', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'created_at';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $accountings = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();

        return view('admin.accountings.index', compact('accountings'));
    }

    public function create(Request $request, CarryForward $carryForward)
    {
        $contacts = Contact::orderBy('last_name')->get();
        // With their people: the owner search shows a person's band next to the name
        $organizations = Organization::with('contacts')->orderBy('names')->get();
        $templates = ChartTemplate::with(['accounts' => fn($q) => $q->orderBy('number')])->orderBy('name')->get();
        $year = now()->year;

        // Every accounting can be the year a new one opens from (Saldovortrag)
        $sources = Accounting::with(['accounts', 'accountable', 'nextAccountings'])
            ->orderByDesc('period_end')->orderBy('name')->get();
        $balances = $carryForward->balances($sources);
        $sourcesJson = $sources->map(fn (Accounting $a) => [
            'id' => $a->id,
            'name' => $a->name,
            'owner' => $a->accountable_name,
            'currency' => $a->currency,
            'period_start' => $a->period_start->toDateString(),
            'period_end' => $a->period_end->toDateString(),
            'is_closed' => $a->is_closed,
            'next' => $a->nextAccountings->pluck('name')->values(),
            'profit_account_id' => $carryForward->defaultProfitAccount($a->accounts)?->id,
            'accounts' => $a->accounts->where('is_header', false)->map(fn (Account $acc) => [
                'id' => $acc->id, 'number' => $acc->number, 'name' => $acc->name, 'type' => $acc->type,
                'balance' => $balances[$acc->id] ?? 0,
            ])->values(),
        ])->values();
        $fromId = $sources->contains('id', (int) $request->query('from')) ? (int) $request->query('from') : null;

        return view('admin.accountings.create', compact('contacts', 'organizations', 'templates', 'year', 'sources', 'sourcesJson', 'fromId'));
    }

    public function store(Request $request, CarryForward $carryForward)
    {
        if ($request->filled('source_accounting_id')) {
            return $this->storeFromPrevious($request, $carryForward);
        }

        $validated = $request->validate([
            'accountable_type' => 'required|in:contact,organization',
            'accountable_id' => 'required|integer',
            'name' => 'required|string|max:255',
            'currency' => 'required|in:CHF,EUR,USD',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'chart_template_id' => 'nullable|exists:chart_templates,id',
            'notes' => 'nullable|string',
            'custom_accounts' => 'nullable|array',
            'custom_accounts.*.number' => 'required_with:custom_accounts|string|max:20',
            'custom_accounts.*.name' => 'required_with:custom_accounts|string|max:255',
            'custom_accounts.*.type' => 'required_with:custom_accounts|in:asset,liability,income,expense',
            'custom_accounts.*.is_header' => 'nullable|boolean',
            'custom_accounts.*.opening_balance' => 'nullable|numeric',
        ]);

        $type = $validated['accountable_type'] === 'contact' ? Contact::class : Organization::class;
        $entity = $type::findOrFail($validated['accountable_id']);

        $accounting = Accounting::create([
            'accountable_type' => $type,
            'accountable_id' => $entity->id,
            'name' => $validated['name'],
            'currency' => $validated['currency'],
            'period_start' => $validated['period_start'],
            'period_end' => $validated['period_end'],
            'chart_template_id' => $validated['chart_template_id'],
            'notes' => $validated['notes'],
        ]);

        if (!empty($validated['custom_accounts'])) {
            // Manuell angepasster Kontenplan (Schritt 2)
            $sortOrder = 0;
            foreach ($validated['custom_accounts'] as $acc) {
                $accounting->accounts()->create([
                    'number' => $acc['number'],
                    'name' => $acc['name'],
                    'type' => $acc['type'],
                    'is_header' => !empty($acc['is_header']),
                    'opening_balance' => $acc['opening_balance'] ?? 0,
                    'sort_order' => $sortOrder++,
                ]);
            }
        } elseif ($validated['chart_template_id']) {
            $template = ChartTemplate::findOrFail($validated['chart_template_id']);
            $accounting->applyTemplate($template);
        }

        return redirect()->route('admin.accountings.show', $accounting)->with('success', 'Buchhaltung erstellt.');
    }

    /** The next fiscal year: chart and closing balances of the chosen one, its profit onto an equity account. */
    private function storeFromPrevious(Request $request, CarryForward $carryForward)
    {
        $validated = $request->validate([
            'source_accounting_id' => 'required|exists:accountings,id',
            'name' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'profit_account_id' => 'required|integer',
            'close_source' => 'nullable|boolean',
            'notes' => 'nullable|string',
        ], [
            'profit_account_id.required' => 'Bitte das Eigenkapitalkonto wählen, das den Gewinn oder Verlust übernimmt.',
        ]);

        $previous = Accounting::with('accounts')->findOrFail($validated['source_accounting_id']);
        $profitAccount = $previous->accounts->where('is_header', false)->where('type', 'liability')
            ->firstWhere('id', (int) $validated['profit_account_id']);

        $errors = [];
        if (!$profitAccount) {
            $errors['profit_account_id'] = "Bitte ein Passivkonto (Eigenkapital) aus «{$previous->name}» wählen.";
        }
        if (\Illuminate\Support\Carbon::parse($validated['period_start'])->lte($previous->period_end)) {
            $errors['period_start'] = "Das neue Geschäftsjahr muss nach dem Ende von «{$previous->name}» ({$previous->period_end->format('d.m.Y')}) beginnen.";
        }
        if ($errors) {
            throw \Illuminate\Validation\ValidationException::withMessages($errors);
        }

        $next = $carryForward->open($previous, $validated, $profitAccount);

        $message = "«{$next->name}» eröffnet – Schlusssalden von «{$previous->name}» übernommen.";
        if ($request->boolean('close_source') && !$previous->is_closed) {
            $previous->update(['status' => 'closed']);
            $message .= " «{$previous->name}» ist abgeschlossen.";
        }

        return redirect()->route('admin.accountings.show', $next)->with('success', $message);
    }

    /** Takes the previous year's closing balances over again, after a late booking there. */
    public function refreshOpenings(Accounting $accounting, CarryForward $carryForward)
    {
        if ($accounting->is_closed) {
            return back()->with('error', 'Buchhaltung ist abgeschlossen.');
        }
        if (!$accounting->previousAccounting) {
            return back()->with('error', 'Diese Buchhaltung wurde nicht aus einem Vorjahr eröffnet.');
        }

        $changed = $carryForward->refresh($accounting);

        return back()->with('success', $changed
            ? "Eröffnungssaldi aus «{$accounting->previousAccounting->name}» übernommen ({$changed} " . ($changed === 1 ? 'Konto' : 'Konten') . ').'
            : 'Die Eröffnungssaldi stimmen schon.');
    }

    public function show(Accounting $accounting, CarryForward $carryForward)
    {
        $accounting->load(['accountable', 'chartTemplate', 'previousAccounting', 'profitAccount', 'nextAccountings'])
            ->loadAccountTotals();
        $openingDifferences = $carryForward->differences($accounting);
        $bookingsCount = $accounting->bookings()->count();

        // Everything below works on the loaded accounts - no query per account
        $accounts = $accounting->accounts->where('is_header', false);
        // Net per account, as in the income statement: a refund on an income account lowers the income
        $totalIncome = round($accounts->where('type', 'income')->sum(fn ($a) => $a->balance), 2);
        $totalExpenses = round($accounts->where('type', 'expense')->sum(fn ($a) => $a->balance), 2);

        // Chart-Daten: Top Ertrags- und Aufwandkonten
        $topAccounts = fn (string $type) => $accounts->where('type', $type)
            ->map(fn ($a) => ['name' => $a->number . ' ' . $a->name, 'balance' => abs($a->balance)])
            ->filter(fn ($a) => $a['balance'] > 0)
            ->sortByDesc('balance')->take(8)->values();
        $incomeByAccount = $topAccounts('income');
        $expenseByAccount = $topAccounts('expense');

        // Zwischensumme pro Überschriften-Konto
        $headerTotals = ChartGroups::headerTotals($accounting->accounts, fn ($a) => $a->balance);

        $openBankCount = $accounting->bankTransactions()->where('status', \App\Models\BankTransaction::OPEN)->count();

        return view('admin.accountings.show', compact(
            'accounting', 'bookingsCount', 'totalIncome', 'totalExpenses',
            'incomeByAccount', 'expenseByAccount', 'openBankCount', 'openingDifferences', 'headerTotals'
        ));
    }

    public function edit(Accounting $accounting)
    {
        return view('admin.accountings.edit', compact('accounting'));
    }

    public function update(Request $request, Accounting $accounting)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
            'notes' => 'nullable|string',
        ]);

        $accounting->update($validated);

        return redirect()->route('admin.accountings.show', $accounting)->with('success', 'Buchhaltung aktualisiert.');
    }

    public function destroy(Accounting $accounting)
    {
        if ($accounting->nextAccountings()->exists()) {
            // The next year keeps its opening balances - it just no longer knows where they came from
            $accounting->nextAccountings()->update(['previous_accounting_id' => null]);
        }
        $accounting->delete();
        return redirect()->route('admin.accountings.index')->with('success', 'Buchhaltung gelöscht.');
    }

    public function close(Accounting $accounting)
    {
        $accounting->update(['status' => 'closed']);
        return back()->with('success', 'Buchhaltung abgeschlossen.');
    }

    public function reopen(Accounting $accounting)
    {
        $accounting->update(['status' => 'open']);
        return back()->with('success', 'Buchhaltung wieder geöffnet.');
    }

    public function journal(Request $request, Accounting $accounting)
    {
        $accounting->load('accountable');
        $query = $accounting->bookings()->with(['debitAccount', 'creditAccount', 'project', 'contact', 'organization', 'documents',
            'expense' => fn ($q) => $q->withCount('documents')]);

        if ($from = $request->input('from')) {
            $query->where('booking_date', '>=', $from);
        }
        if ($to = $request->input('to')) {
            $query->where('booking_date', '<=', $to);
        }
        if ($projectId = $request->input('project_id')) {
            $query->where('project_id', $projectId);
        }
        if ($contactId = $request->input('contact_id')) {
            $query->where('contact_id', $contactId);
        }
        if ($organizationId = $request->input('organization_id')) {
            $query->where('organization_id', $organizationId);
        }

        $bookings = $query->orderBy('booking_date')->orderBy('id')->paginate(50)->withQueryString();

        $projects = Project::whereIn('id', $accounting->bookings()->whereNotNull('project_id')->distinct()->pluck('project_id'))->orderBy('name')->get();
        $contacts = Contact::whereIn('id', $accounting->bookings()->whereNotNull('contact_id')->distinct()->pluck('contact_id'))->orderBy('last_name')->get();
        $organizations = Organization::whereIn('id', $accounting->bookings()->whereNotNull('organization_id')->distinct()->pluck('organization_id'))->orderBy('names')->get();

        return view('admin.accountings.journal', compact('accounting', 'bookings', 'projects', 'contacts', 'organizations'));
    }

    public function ledger(Accounting $accounting, Account $account)
    {
        $accounting->load('accountable');
        $bookings = $account->debitBookings()
            ->select('bookings.*')
            ->selectRaw("'debit' as side")
            ->union(
                $account->creditBookings()
                    ->select('bookings.*')
                    ->selectRaw("'credit' as side")
            )
            ->orderBy('booking_date')
            ->orderBy('id')
            ->get();

        // Manually load the related accounts
        $allBookingIds = $bookings->pluck('id');
        $allBookings = \App\Models\Booking::with(['debitAccount', 'creditAccount'])
            ->whereIn('id', $allBookingIds)
            ->get()
            ->keyBy('id');

        return view('admin.accountings.ledger', compact('accounting', 'account', 'bookings', 'allBookings'));
    }

    public function trialBalance(Accounting $accounting)
    {
        $accounting->load('accountable')->loadAccountTotals();

        $typeLabels = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];
        $grouped = $accounting->accounts->where('is_header', false)->groupBy('type');

        return view('admin.accountings.trial-balance', compact('accounting', 'grouped', 'typeLabels'));
    }

    public function balanceSheet(Accounting $accounting)
    {
        $accounting->load('accountable')->loadAccountTotals();

        $assets = $accounting->accounts->where('is_header', false)->where('type', 'asset');
        $liabilities = $accounting->accounts->where('is_header', false)->where('type', 'liability');
        // Grouped under their header accounts, with subtotals
        $assetGroups = ChartGroups::of($accounting->accounts->where('type', 'asset'));
        $liabilityGroups = ChartGroups::of($accounting->accounts->where('type', 'liability'));

        return view('admin.accountings.balance-sheet', compact('accounting', 'assets', 'liabilities', 'assetGroups', 'liabilityGroups'));
    }

    public function incomeStatement(Accounting $accounting)
    {
        $accounting->load('accountable')->loadAccountTotals();

        $incomeAccounts = $accounting->accounts->where('is_header', false)->where('type', 'income');
        $expenseAccounts = $accounting->accounts->where('is_header', false)->where('type', 'expense');
        $incomeGroups = ChartGroups::of($accounting->accounts->where('type', 'income'));
        $expenseGroups = ChartGroups::of($accounting->accounts->where('type', 'expense'));

        return view('admin.accountings.income-statement', compact('accounting', 'incomeAccounts', 'expenseAccounts', 'incomeGroups', 'expenseGroups'));
    }

    public function storeAccount(Request $request, Accounting $accounting)
    {
        if ($accounting->is_closed) {
            return back()->with('error', 'Buchhaltung ist abgeschlossen.');
        }

        $validated = $request->validate([
            'number' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,income,expense',
            'is_header' => 'boolean',
            'opening_balance' => 'nullable|numeric',
        ]);

        $validated['is_header'] = $request->boolean('is_header');
        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['sort_order'] = (int) $validated['number'];

        $accounting->accounts()->create($validated);

        return back()->with('success', 'Konto hinzugefügt.');
    }

    public function updateAccount(Request $request, Account $account)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,income,expense',
            'opening_balance' => 'nullable|numeric',
        ]);

        $validated['opening_balance'] = $validated['opening_balance'] ?? 0;
        $validated['sort_order'] = (int) $validated['number'];

        $account->update($validated);

        return back()->with('success', 'Konto aktualisiert.');
    }

    public function destroyAccount(Account $account)
    {
        if ($account->has_bookings) {
            return back()->with('error', 'Konto hat Buchungen und kann nicht gelöscht werden.');
        }

        $account->delete();
        return back()->with('success', 'Konto gelöscht.');
    }
}
