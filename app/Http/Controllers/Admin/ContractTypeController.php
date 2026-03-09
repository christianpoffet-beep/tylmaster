<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use App\Models\ContractType;
use Illuminate\Http\Request;

class ContractTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = ContractType::query();

        if ($search = $request->input('search')) {
            $query->where('name', 'like', "%{$search}%");
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'sort_order';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $types = $query->orderBy($sortField, $sortDir)->paginate(30)->withQueryString();

        $types->getCollection()->transform(function ($type) {
            $type->usage_count = Contract::where('type', $type->slug)->count();
            return $type;
        });

        return view('admin.contract-types.index', compact('types'));
    }

    public function create()
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.contract-types.create', compact('colorOptions'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contract_types,name',
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        ContractType::create([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        return redirect()->route('admin.contract-types.index')->with('success', 'Vertragstyp erstellt.');
    }

    public function edit(ContractType $contractType)
    {
        $colorOptions = $this->getColorOptions();
        return view('admin.contract-types.edit', compact('contractType', 'colorOptions'));
    }

    public function update(Request $request, ContractType $contractType)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:contract_types,name,' . $contractType->id,
            'color' => 'required|string|max:60',
            'sort_order' => 'nullable|integer|min:0',
        ]);

        $oldSlug = $contractType->slug;

        $contractType->update([
            'name' => $request->input('name'),
            'color' => $request->input('color'),
            'sort_order' => $request->input('sort_order', 0),
        ]);

        if ($oldSlug !== $contractType->slug) {
            Contract::where('type', $oldSlug)->update(['type' => $contractType->slug]);
        }

        return redirect()->route('admin.contract-types.index')->with('success', 'Vertragstyp aktualisiert.');
    }

    public function destroy(ContractType $contractType)
    {
        $count = Contract::where('type', $contractType->slug)->count();
        if ($count > 0) {
            return back()->with('error', "Kann nicht gelöscht werden: {$count} Vertrag/Verträge verwenden diesen Typ.");
        }

        $contractType->delete();
        return redirect()->route('admin.contract-types.index')->with('success', 'Vertragstyp gelöscht.');
    }

    private function getColorOptions(): array
    {
        return [
            'bg-purple-100 text-purple-700' => 'Lila',
            'bg-blue-100 text-blue-700' => 'Blau',
            'bg-indigo-100 text-indigo-700' => 'Indigo',
            'bg-green-100 text-green-700' => 'Grün',
            'bg-yellow-100 text-yellow-700' => 'Gelb',
            'bg-pink-100 text-pink-700' => 'Pink',
            'bg-red-100 text-red-700' => 'Rot',
            'bg-orange-100 text-orange-700' => 'Orange',
            'bg-teal-100 text-teal-700' => 'Teal',
            'bg-cyan-100 text-cyan-700' => 'Cyan',
            'bg-gray-100 text-gray-600' => 'Grau',
        ];
    }
}
