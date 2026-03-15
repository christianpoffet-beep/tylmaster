<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\CampaignTemplate;
use Illuminate\Http\Request;

class CampaignTemplateController extends Controller
{
    public function index(Request $request)
    {
        $query = CampaignTemplate::query();

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        $sortField = $request->input('sort', 'sort_order');
        $sortDir = $request->input('dir', 'asc');
        $allowedSorts = ['name', 'sort_order', 'type', 'language', 'created_at'];
        if (!in_array($sortField, $allowedSorts)) $sortField = 'sort_order';
        if (!in_array($sortDir, ['asc', 'desc'])) $sortDir = 'asc';

        $templates = $query->withCount('campaigns')->orderBy($sortField, $sortDir)->paginate(20)->withQueryString();

        return view('admin.campaign-templates.index', compact('templates'));
    }

    public function create()
    {
        return view('admin.campaign-templates.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:email,letter',
            'language' => 'required|string|max:5',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $template = CampaignTemplate::create($validated);

        return redirect()->route('admin.campaign-templates.edit', $template)->with('success', 'Kampagnenvorlage erstellt.');
    }

    public function edit(CampaignTemplate $campaignTemplate)
    {
        return view('admin.campaign-templates.edit', ['template' => $campaignTemplate]);
    }

    public function update(Request $request, CampaignTemplate $campaignTemplate)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'type' => 'required|string|in:email,letter',
            'language' => 'required|string|max:5',
            'subject' => 'nullable|string|max:255',
            'body' => 'nullable|string',
            'notes' => 'nullable|string',
            'sort_order' => 'nullable|integer',
        ]);

        $campaignTemplate->update($validated);

        return redirect()->route('admin.campaign-templates.edit', $campaignTemplate)->with('success', 'Kampagnenvorlage aktualisiert.');
    }

    public function destroy(CampaignTemplate $campaignTemplate)
    {
        $campaignTemplate->delete();
        return redirect()->route('admin.campaign-templates.index')->with('success', 'Kampagnenvorlage gelöscht.');
    }

    public function data(CampaignTemplate $campaignTemplate)
    {
        return response()->json([
            'subject' => $campaignTemplate->subject,
            'body' => $campaignTemplate->body,
            'language' => $campaignTemplate->language,
        ]);
    }
}
