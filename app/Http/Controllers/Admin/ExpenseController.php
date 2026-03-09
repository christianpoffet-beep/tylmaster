<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Contact;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function index(Request $request)
    {
        $query = Expense::with('contact');

        if ($search = $request->input('search')) {
            $query->where('description', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'expense_date');
        $sortDir = $request->input('dir', 'desc');
        $allowedSorts = ['description', 'amount', 'expense_date', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'expense_date';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'desc';

        $expenses = $query->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();
        return view('admin.finances.expenses.index', compact('expenses'));
    }

    public function create()
    {
        $contacts = Contact::orderBy('last_name')->get();
        return view('admin.finances.expenses.create', compact('contacts'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:CHF,EUR,USD',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        Expense::create($validated);
        return redirect()->route('admin.expenses.index')->with('success', 'Ausgabe erfasst.');
    }

    public function edit(Expense $expense)
    {
        $contacts = Contact::orderBy('last_name')->get();
        return view('admin.finances.expenses.edit', compact('expense', 'contacts'));
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'description' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
            'currency' => 'required|in:CHF,EUR,USD',
            'expense_date' => 'required|date',
            'category' => 'nullable|string|max:100',
            'contact_id' => 'nullable|exists:contacts,id',
        ]);

        $expense->update($validated);
        return redirect()->route('admin.expenses.index')->with('success', 'Ausgabe aktualisiert.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('admin.expenses.index')->with('success', 'Ausgabe gelöscht.');
    }
}
