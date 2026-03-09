<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Accounting;
use App\Models\Booking;
use App\Models\Contact;
use App\Models\Document;
use App\Models\Organization;
use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class BookingController extends Controller
{
    public function create(Accounting $accounting)
    {
        if ($accounting->is_closed) {
            return redirect()->route('admin.accountings.show', $accounting)->with('error', 'Buchhaltung ist abgeschlossen.');
        }

        $accounting->load('accountable');
        $accounts = $accounting->accounts()->where('is_header', false)->orderBy('number')->get();
        $projects = Project::orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::orderBy('names')->get();

        return view('admin.bookings.create', compact('accounting', 'accounts', 'projects', 'contacts', 'organizations'));
    }

    public function store(Request $request, Accounting $accounting)
    {
        if ($accounting->is_closed) {
            return redirect()->route('admin.accountings.show', $accounting)->with('error', 'Buchhaltung ist abgeschlossen.');
        }

        $validated = $request->validate([
            'booking_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id|different:debit_account_id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:51200',
        ]);

        $validated['accounting_id'] = $accounting->id;

        $booking = Booking::create($validated);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('bookings', 'public');
                $booking->documents()->create([
                    'title' => $file->getClientOriginalName(),
                    'category' => 'invoice',
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.accountings.journal', $accounting)->with('success', 'Buchung erstellt.');
    }

    public function edit(Booking $booking)
    {
        $accounting = $booking->accounting;

        if ($accounting->is_closed) {
            return redirect()->route('admin.accountings.show', $accounting)->with('error', 'Buchhaltung ist abgeschlossen.');
        }

        $accounting->load('accountable');
        $accounts = $accounting->accounts()->where('is_header', false)->orderBy('number')->get();
        $booking->load('documents');
        $projects = Project::orderBy('name')->get();
        $contacts = Contact::orderBy('last_name')->get();
        $organizations = Organization::orderBy('names')->get();

        return view('admin.bookings.edit', compact('booking', 'accounting', 'accounts', 'projects', 'contacts', 'organizations'));
    }

    public function update(Request $request, Booking $booking)
    {
        $accounting = $booking->accounting;

        if ($accounting->is_closed) {
            return redirect()->route('admin.accountings.show', $accounting)->with('error', 'Buchhaltung ist abgeschlossen.');
        }

        $validated = $request->validate([
            'booking_date' => 'required|date',
            'reference' => 'nullable|string|max:255',
            'description' => 'required|string|max:255',
            'debit_account_id' => 'required|exists:accounts,id',
            'credit_account_id' => 'required|exists:accounts,id|different:debit_account_id',
            'amount' => 'required|numeric|min:0.01',
            'notes' => 'nullable|string',
            'project_id' => 'nullable|exists:projects,id',
            'contact_id' => 'nullable|exists:contacts,id',
            'organization_id' => 'nullable|exists:organizations,id',
            'documents' => 'nullable|array',
            'documents.*' => 'file|max:51200',
        ]);

        $booking->update($validated);

        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $file) {
                $path = $file->store('bookings', 'public');
                $booking->documents()->create([
                    'title' => $file->getClientOriginalName(),
                    'category' => 'invoice',
                    'file_path' => $path,
                    'file_size' => $file->getSize(),
                    'mime_type' => $file->getMimeType(),
                ]);
            }
        }

        return redirect()->route('admin.accountings.journal', $accounting)->with('success', 'Buchung aktualisiert.');
    }

    public function destroy(Booking $booking)
    {
        $accounting = $booking->accounting;

        if ($accounting->is_closed) {
            return redirect()->route('admin.accountings.show', $accounting)->with('error', 'Buchhaltung ist abgeschlossen.');
        }

        foreach ($booking->documents as $doc) {
            Storage::disk('public')->delete($doc->file_path);
            $doc->forceDelete();
        }

        $booking->delete();

        return redirect()->route('admin.accountings.journal', $accounting)->with('success', 'Buchung gelöscht.');
    }

    public function destroyDocument(Booking $booking, Document $document)
    {
        if ($document->documentable_id !== $booking->id || $document->documentable_type !== Booking::class) {
            abort(403);
        }

        Storage::disk('public')->delete($document->file_path);
        $document->forceDelete();

        return back()->with('success', 'Beleg gelöscht.');
    }
}
