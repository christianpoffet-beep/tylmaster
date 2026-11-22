<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ContractTemplate;
use App\Models\Organization;
use App\Services\Royalties\RoyaltyCoverage;
use App\Services\Royalties\RoyaltySettings;
use Illuminate\Http\Request;

/**
 * Contracts for the statements: which products with usage are covered by a
 * contract, and a quick way to the contract for the ones that are not.
 */
class RoyaltyContractController extends Controller
{
    public function index(Request $request, RoyaltyCoverage $coverage)
    {
        $templates = ContractTemplate::orderBy('sort_order')->get(['id', 'name']);
        $template = $templates->firstWhere('id', $request->integer('template'))
            ?? $templates->first(fn ($t) => mb_strtolower($t->name) === 'label standard')
            ?? $templates->first(fn ($t) => str_contains(mb_strtolower($t->name), 'label'))
            ?? $templates->first();

        $result = $coverage->build($request->boolean('all'));

        return view('admin.royalties.contracts', [
            'products' => $result['products'],
            'looseTracks' => $result['loose_tracks'],
            'openAmount' => $result['open_amount'],
            'own' => $coverage->own(),
            'labels' => Organization::where('type', 'label')->get()->sortBy(fn ($o) => mb_strtolower($o->primary_name))->values(),
            'templates' => $templates,
            'template' => $template,
            'all' => $request->boolean('all'),
        ]);
    }

    public function setOwnLabel(Request $request)
    {
        $data = $request->validate([
            'organization_ids' => 'required|array|min:1',
            'organization_ids.*' => 'integer|exists:organizations,id',
        ], [
            'organization_ids.required' => 'Mindestens ein eigenes Label wählen.',
        ]);
        RoyaltySettings::setOwnOrganizations($data['organization_ids']);

        $count = count($data['organization_ids']);

        return back()->with('success', $count === 1 ? 'Eigenes Label gespeichert.' : "{$count} eigene Labels gespeichert.");
    }
}
