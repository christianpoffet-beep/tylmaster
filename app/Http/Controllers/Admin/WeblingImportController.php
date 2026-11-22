<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ChartTemplate;
use App\Models\Contact;
use App\Models\Organization;
use App\Services\Webling\WeblingException;
use App\Services\Webling\WeblingImporter;
use Illuminate\Http\Request;

/**
 * A year kept in Webling as an accounting here - from the export "Bilanz und
 * Erfolgsrechnung". The file is read and dropped, nothing of it is stored.
 */
class WeblingImportController extends Controller
{
    public function create()
    {
        return view('admin.accountings.import-webling', [
            'contacts' => Contact::orderBy('last_name')->get(),
            'organizations' => Organization::with('contacts')->orderBy('names')->get(),
            'templates' => ChartTemplate::withCount('accounts')->orderBy('name')->get(),
        ]);
    }

    public function store(Request $request, WeblingImporter $importer)
    {
        $validated = $request->validate([
            'file' => 'required|file|mimes:xlsx|max:10240',
            'accountable_type' => 'required|in:contact,organization',
            'accountable_id' => 'required|integer',
            'name' => 'nullable|string|max:255',
            'chart_template_id' => 'nullable|exists:chart_templates,id',
        ], [
            'file.mimes' => 'Bitte den Webling-Export als Excel-Datei (.xlsx) wählen.',
            'accountable_id.required' => 'Bitte eine Organisation oder Person wählen.',
        ]);

        $owner = ($validated['accountable_type'] === 'contact' ? Contact::class : Organization::class)::findOrFail($validated['accountable_id']);
        $template = !empty($validated['chart_template_id']) ? ChartTemplate::find($validated['chart_template_id']) : null;

        try {
            $result = $importer->import($request->file('file')->getRealPath(), $owner, $validated['name'] ?? null, $template,
                $request->file('file')->getClientOriginalName());
        } catch (WeblingException $e) {
            return back()->withInput()->withErrors(['file' => $e->getMessage()]);
        }

        $accounting = $result['accounting'];
        $message = "«{$accounting->name}» aus Webling übernommen: " . $accounting->bookings()->count() . ' Sammelbuchungen, Schlussbilanz wie in Webling.';
        if ($result['added']) {
            $message .= ' Nicht in der Vorlage, neu angelegt: ' . implode(', ', $result['added']) . '.';
        }

        $redirect = redirect()->route('admin.accountings.show', $accounting);

        return $result['warnings']
            ? $redirect->with('warning', 'Übernommen, aber: ' . implode(' ', $result['warnings']))
            : $redirect->with('success', $message);
    }
}
