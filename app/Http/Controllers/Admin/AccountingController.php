<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Accounting;
use App\Models\ChartTemplate;
use App\Models\Contact;
use App\Models\Organization;
use App\Models\Project;
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

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::orderBy('names')->get();
        $templates = ChartTemplate::with(['accounts' => fn($q) => $q->orderBy('number')])->orderBy('name')->get();
        $year = now()->year;

        return view('admin.accountings.create', compact('contacts', 'organizations', 'templates', 'year'));
    }

    public function store(Request $request)
    {
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

    public function show(Accounting $accounting)
    {
        $accounting->load(['accountable', 'accounts', 'chartTemplate']);
        $bookingsCount = $accounting->bookings()->count();

        $totalDebit = $accounting->bookings()->sum('amount');
        $incomeAccounts = $accounting->accounts()->where('type', 'income')->pluck('id');
        $expenseAccounts = $accounting->accounts()->where('type', 'expense')->pluck('id');

        $totalIncome = $accounting->bookings()->whereIn('credit_account_id', $incomeAccounts)->sum('amount');
        $totalExpenses = $accounting->bookings()->whereIn('debit_account_id', $expenseAccounts)->sum('amount');

        // Chart-Daten: Top Ertragskonten
        $incomeByAccount = $accounting->accounts()
            ->where('type', 'income')->where('is_header', false)->get()
            ->map(fn($a) => ['name' => $a->number . ' ' . $a->name, 'balance' => abs($a->balance)])
            ->filter(fn($a) => $a['balance'] > 0)
            ->sortByDesc('balance')->take(8)->values();

        // Chart-Daten: Top Aufwandkonten
        $expenseByAccount = $accounting->accounts()
            ->where('type', 'expense')->where('is_header', false)->get()
            ->map(fn($a) => ['name' => $a->number . ' ' . $a->name, 'balance' => abs($a->balance)])
            ->filter(fn($a) => $a['balance'] > 0)
            ->sortByDesc('balance')->take(8)->values();

        return view('admin.accountings.show', compact(
            'accounting', 'bookingsCount', 'totalDebit', 'totalIncome', 'totalExpenses',
            'incomeByAccount', 'expenseByAccount'
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
        $query = $accounting->bookings()->with(['debitAccount', 'creditAccount', 'project', 'contact', 'organization', 'documents']);

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
        $accounting->load(['accountable', 'accounts']);

        $typeLabels = ['asset' => 'Aktiven', 'liability' => 'Passiven', 'income' => 'Ertrag', 'expense' => 'Aufwand'];
        $grouped = $accounting->accounts->where('is_header', false)->groupBy('type');

        return view('admin.accountings.trial-balance', compact('accounting', 'grouped', 'typeLabels'));
    }

    public function balanceSheet(Accounting $accounting)
    {
        $accounting->load(['accountable', 'accounts']);

        $assets = $accounting->accounts->where('is_header', false)->where('type', 'asset');
        $liabilities = $accounting->accounts->where('is_header', false)->where('type', 'liability');

        return view('admin.accountings.balance-sheet', compact('accounting', 'assets', 'liabilities'));
    }

    public function incomeStatement(Accounting $accounting)
    {
        $accounting->load(['accountable', 'accounts']);

        $incomeAccounts = $accounting->accounts->where('is_header', false)->where('type', 'income');
        $expenseAccounts = $accounting->accounts->where('is_header', false)->where('type', 'expense');

        return view('admin.accountings.income-statement', compact('accounting', 'incomeAccounts', 'expenseAccounts'));
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
