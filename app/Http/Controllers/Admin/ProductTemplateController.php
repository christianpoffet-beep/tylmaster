<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ProductTemplate;
use App\Models\Setting;
use Illuminate\Http\Request;

class ProductTemplateController extends Controller
{
    public function index()
    {
        $productTemplates = ProductTemplate::orderBy('sort_order')->get();
        return view('admin.product-templates.index', compact('productTemplates'));
    }

    public function create()
    {
        $productTypes = Setting::productTypes();
        return view('admin.product-templates.create', compact('productTypes'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'nullable|string|max:10',
            'default_product_types' => 'nullable|array',
            'default_release_info' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        ProductTemplate::create($validated);
        return redirect()->route('admin.product-templates.index')->with('success', 'Produktvorlage erstellt.');
    }

    public function edit(ProductTemplate $productTemplate)
    {
        $productTypes = Setting::productTypes();
        return view('admin.product-templates.edit', compact('productTemplate', 'productTypes'));
    }

    public function update(Request $request, ProductTemplate $productTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'language' => 'nullable|string|max:10',
            'default_product_types' => 'nullable|array',
            'default_release_info' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $productTemplate->update($validated);
        return redirect()->route('admin.product-templates.index')->with('success', 'Produktvorlage aktualisiert.');
    }

    public function destroy(ProductTemplate $productTemplate)
    {
        $productTemplate->delete();
        return redirect()->route('admin.product-templates.index')->with('success', 'Produktvorlage gelöscht.');
    }

    public function data(ProductTemplate $productTemplate)
    {
        return response()->json([
            'default_product_types' => $productTemplate->default_product_types,
            'default_release_info' => $productTemplate->default_release_info,
        ]);
    }
}
