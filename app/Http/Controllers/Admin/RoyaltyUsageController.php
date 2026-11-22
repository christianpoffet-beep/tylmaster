<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Release;
use App\Models\Track;
use App\Services\Royalties\RoyaltyUsage;
use Illuminate\Http\Request;

/** The usage of a track or a product (?track= / ?release=): when, where, on which service and subscription. */
class RoyaltyUsageController extends Controller
{
    public function show(Request $request)
    {
        $subject = $request->filled('track')
            ? Track::with('organizations')->findOrFail($request->integer('track'))
            : Release::findOrFail($request->integer('release'));
        $usage = new RoyaltyUsage($subject, $request->only(RoyaltyUsage::FILTERS));

        return view('admin.royalties.usage', [
            'usage' => $usage,
            'subject' => $subject,
            'isTrack' => $subject instanceof Track,
            'totals' => $usage->totals(),
            'months' => $usage->months(),
            'countries' => $usage->ranking('country'),
            'portals' => $usage->ranking('portal'),
            'streamTypes' => $usage->ranking('stream_type'),
            'options' => $usage->options(),
            'lines' => $usage->query()->orderByDesc('period')->orderByDesc('quantity')->paginate(50)->withQueryString(),
        ]);
    }
}
