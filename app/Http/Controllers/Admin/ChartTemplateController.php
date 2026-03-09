<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartTemplate;
use App\Models\ChartTemplateAccount;
use App\Models\OrganizationType;
use Illuminate\Http\Request;

class ChartTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = ChartTemplate::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $templates = $query->withCount('accounts')->orderBy('name')->paginate(20)->withQueryString();
        $orgTypes = OrganizationType::orderBy('sort_order')->get();

        return view('admin.chart-templates.index', compact('templates', 'orgTypes'));
    }

    public function create()
    {
        $orgTypes = OrganizationType::orderBy('sort_order')->get();
        return view('admin.chart-templates.create', compact('orgTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'organization_type_slug' => 'nullable|exists:organization_types,slug',
        ]);

        $template = ChartTemplate::create($validated);

        return redirect()->route('admin.chart-templates.show', $template)->with('success', 'Kontoplan-Vorlage erstellt.');
    }

    public function show(ChartTemplate $chartTemplate)
    {
        $chartTemplate->load('accounts');
        $accountTypes = [
            'asset' => 'Aktiven',
            'liability' => 'Passiven',
            'income' => 'Ertrag',
            'expense' => 'Aufwand',
        ];
        return view('admin.chart-templates.show', compact('chartTemplate', 'accountTypes'));
    }

    public function edit(ChartTemplate $chartTemplate)
    {
        $orgTypes = OrganizationType::orderBy('sort_order')->get();
        return view('admin.chart-templates.edit', compact('chartTemplate', 'orgTypes'));
    }

    public function update(Request $request, ChartTemplate $chartTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'organization_type_slug' => 'nullable|exists:organization_types,slug',
        ]);

        $chartTemplate->update($validated);

        return redirect()->route('admin.chart-templates.show', $chartTemplate)->with('success', 'Vorlage aktualisiert.');
    }

    public function destroy(ChartTemplate $chartTemplate)
    {
        if ($chartTemplate->usage_count > 0) {
            return back()->with('error', "Kann nicht gelöscht werden: {$chartTemplate->usage_count} Buchhaltung(en) verwenden diese Vorlage.");
        }

        $chartTemplate->delete();
        return redirect()->route('admin.chart-templates.index')->with('success', 'Vorlage gelöscht.');
    }

    public function storeAccount(Request $request, ChartTemplate $chartTemplate)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,income,expense',
            'parent_number' => 'nullable|string|max:10',
            'is_header' => 'boolean',
        ]);

        $validated['is_header'] = $request->boolean('is_header');
        $validated['sort_order'] = (int) $validated['number'];

        $chartTemplate->accounts()->create($validated);

        return back()->with('success', 'Konto hinzugefügt.');
    }

    public function updateAccount(Request $request, ChartTemplateAccount $account)
    {
        $validated = $request->validate([
            'number' => 'required|string|max:10',
            'name' => 'required|string|max:255',
            'type' => 'required|in:asset,liability,income,expense',
            'parent_number' => 'nullable|string|max:10',
            'is_header' => 'boolean',
        ]);

        $validated['is_header'] = $request->boolean('is_header');
        $validated['sort_order'] = (int) $validated['number'];

        $account->update($validated);

        return back()->with('success', 'Konto aktualisiert.');
    }

    public function destroyAccount(ChartTemplateAccount $account)
    {
        $account->delete();
        return back()->with('success', 'Konto entfernt.');
    }
}
