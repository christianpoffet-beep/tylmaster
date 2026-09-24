<?php

namespace App\Http\Controllers\Concerns;

use App\Models\ArtworkLogo;
use App\Models\ContractTemplate;
use Illuminate\Http\Request;

/**
 * Resolves the logo a contract or a contract template prints in its PDF.
 *
 * Contracts and templates offer the same picker, so they share the rules for
 * reading it back.
 */
trait HandlesContractLogo
{
    /**
     * Write the logo_* keys into $validated.
     *
     * logo_source: keep | none | artwork | upload | template
     *  - keep     → leave the existing logo_path untouched (default)
     *  - none     → remove the logo
     *  - artwork  → reference an existing ArtworkLogo's file
     *  - upload   → store the freshly uploaded file
     *  - template → take the file the chosen contract template carries
     */
    protected function applyLogoSelection(Request $request, array &$validated, ?ContractTemplate $template = null): void
    {
        $validated['logo_in_header'] = $request->boolean('logo_in_header');
        $validated['logo_as_watermark'] = $request->boolean('logo_as_watermark');

        $source = $request->input('logo_source', 'keep');

        switch ($source) {
            case 'none':
                $validated['logo_path'] = null;
                break;

            case 'upload':
                if ($request->hasFile('logo_file')) {
                    $validated['logo_path'] = $request->file('logo_file')->store('contracts/logos', 'public');
                } else {
                    unset($validated['logo_path']); // nothing uploaded → keep current
                }
                break;

            case 'artwork':
                $logo = $request->filled('artwork_logo_id')
                    ? ArtworkLogo::find($request->input('artwork_logo_id'))
                    : null;
                if ($logo) {
                    $validated['logo_path'] = $logo->file_path;
                } else {
                    unset($validated['logo_path']);
                }
                break;

            case 'template':
                if ($template && $template->logo_path) {
                    $validated['logo_path'] = $template->logo_path;
                } else {
                    unset($validated['logo_path']);
                }
                break;

            case 'keep':
            default:
                unset($validated['logo_path']); // do not modify
                break;
        }
    }
}
