<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\ContactController;
use App\Http\Controllers\Admin\ContractController;
use App\Http\Controllers\Admin\DocumentController;
use App\Http\Controllers\Admin\TrackController;
use App\Http\Controllers\Admin\ReleaseController;
use App\Http\Controllers\Admin\ProjectController;
use App\Http\Controllers\Admin\InvoiceController;
use App\Http\Controllers\Admin\ExpenseController;
use App\Http\Controllers\Admin\SubmissionController;
use App\Http\Controllers\Admin\TaskController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\ArtworkController;
use App\Http\Controllers\Admin\GenreController;
use App\Http\Controllers\Admin\OrganizationTypeController;
use App\Http\Controllers\Admin\ContactTypeController;
use App\Http\Controllers\Admin\ProjectTypeController;
use App\Http\Controllers\Admin\PhotoController;
use App\Http\Controllers\Admin\ChartTemplateController;
use App\Http\Controllers\Admin\AccountingController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Admin\ContractTypeController;
use App\Http\Controllers\Admin\InvoiceTemplateController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\PublicGalleryController;
use Illuminate\Support\Facades\Route;

// Public photo/gallery routes (no auth)
Route::get('dl/{path}', [PublicGalleryController::class, 'downloadPhoto'])->where('path', '.*');
Route::get('p/{path}', [PublicGalleryController::class, 'showPhoto'])->where('path', '.*');
Route::get('gallery/{token}/download', [PublicGalleryController::class, 'downloadFolder']);
Route::get('gallery/{token}', [PublicGalleryController::class, 'showGallery']);

// Redirect root to admin dashboard
Route::redirect('/', '/admin');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Admin routes
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::view('help', 'admin.help')->name('help');
    Route::resource('contacts', ContactController::class);
    Route::resource('organizations', OrganizationController::class);
    Route::get('organizations-search', [OrganizationController::class, 'search'])->name('organizations.search');
    Route::post('organizations-quick', [OrganizationController::class, 'storeQuick'])->name('organizations.storeQuick');
    Route::resource('genres', GenreController::class)->except('show');
    Route::resource('organization-types', OrganizationTypeController::class)->except('show');
    Route::resource('contact-types', ContactTypeController::class)->except('show');
    Route::resource('project-types', ProjectTypeController::class)->except('show');
    Route::resource('contract-types', ContractTypeController::class)->except('show');
    Route::resource('contracts', ContractController::class)->except(['destroy']);
    Route::patch('contracts/{contract}/documents/{document}/archive', [ContractController::class, 'archiveDocument'])->name('contracts.documents.archive');
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::resource('tracks', TrackController::class);
    Route::resource('releases', ReleaseController::class);
    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::patch('projects/{project}/tasks/{task}/toggle', [ProjectController::class, 'toggleTask'])->name('projects.tasks.toggle');
    Route::resource('artworks', ArtworkController::class);
    Route::delete('artworks/{artwork}/logos/{logo}', [ArtworkController::class, 'destroyLogo'])->name('artworks.logos.destroy');
    Route::get('credits-search', [ArtworkController::class, 'creditSearch'])->name('credits.search');

    // Photos / Bilder
    Route::get('photos', [PhotoController::class, 'index'])->name('photos.index');
    Route::get('photos/folders/create', [PhotoController::class, 'createFolder'])->name('photos.folders.create');
    Route::post('photos/folders', [PhotoController::class, 'storeFolder'])->name('photos.folders.store');
    Route::get('photos/folders/{folder}', [PhotoController::class, 'showFolder'])->name('photos.folders.show');
    Route::get('photos/folders/{folder}/edit', [PhotoController::class, 'editFolder'])->name('photos.folders.edit');
    Route::put('photos/folders/{folder}', [PhotoController::class, 'updateFolder'])->name('photos.folders.update');
    Route::delete('photos/folders/{folder}', [PhotoController::class, 'destroyFolder'])->name('photos.folders.destroy');
    Route::post('photos/folders/{folder}/upload', [PhotoController::class, 'uploadPhotos'])->name('photos.upload');
    Route::post('photos/folders/{folder}/share', [PhotoController::class, 'generateShareLink'])->name('photos.folders.share');
    Route::delete('photos/folders/{folder}/share', [PhotoController::class, 'revokeShareLink'])->name('photos.folders.revoke');
    Route::get('photos/{photo}', [PhotoController::class, 'showPhoto'])->name('photos.show');
    Route::put('photos/{photo}', [PhotoController::class, 'updatePhoto'])->name('photos.update');
    Route::delete('photos/{photo}', [PhotoController::class, 'destroyPhoto'])->name('photos.destroy');
    Route::post('contacts-quick', [ContactController::class, 'storeQuick'])->name('contacts.storeQuick');
    Route::get('contacts-search', [ContactController::class, 'search'])->name('contacts.search');
    Route::get('projects-search', [ProjectController::class, 'search'])->name('projects.search');
    Route::get('contracts-search', [ContractController::class, 'search'])->name('contracts.search');
    Route::resource('tasks', TaskController::class);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('tasks/{task}/documents/{document}', [TaskController::class, 'destroyDocument'])->name('tasks.documents.destroy');
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::patch('invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::get('invoice-templates/{invoiceTemplate}/data', [InvoiceController::class, 'templateData'])->name('invoice-templates.data');
    Route::get('organizations/{organization}/contacts', [InvoiceController::class, 'organizationContacts'])->name('organizations.contacts');
    Route::get('accountings/{accounting}/accounts', [InvoiceController::class, 'accountingAccounts'])->name('accountings.accounts');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::patch('submissions/{submission}/status', [SubmissionController::class, 'updateStatus'])->name('submissions.updateStatus');
    Route::post('submissions/{submission}/import', [SubmissionController::class, 'import'])->name('submissions.import');
    Route::delete('submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');

    // Buchhaltung
    Route::resource('accountings', AccountingController::class);
    Route::patch('accountings/{accounting}/close', [AccountingController::class, 'close'])->name('accountings.close');
    Route::patch('accountings/{accounting}/reopen', [AccountingController::class, 'reopen'])->name('accountings.reopen');
    Route::get('accountings/{accounting}/journal', [AccountingController::class, 'journal'])->name('accountings.journal');
    Route::get('accountings/{accounting}/ledger/{account}', [AccountingController::class, 'ledger'])->name('accountings.ledger');
    Route::get('accountings/{accounting}/trial-balance', [AccountingController::class, 'trialBalance'])->name('accountings.trialBalance');
    Route::get('accountings/{accounting}/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accountings.balanceSheet');
    Route::get('accountings/{accounting}/income-statement', [AccountingController::class, 'incomeStatement'])->name('accountings.incomeStatement');
    Route::post('accountings/{accounting}/accounts', [AccountingController::class, 'storeAccount'])->name('accountings.accounts.store');
    Route::put('accounting-accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accountings.accounts.update');
    Route::delete('accounting-accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accountings.accounts.destroy');

    // Buchungen
    Route::get('accountings/{accounting}/bookings/create', [BookingController::class, 'create'])->name('bookings.create');
    Route::post('accountings/{accounting}/bookings', [BookingController::class, 'store'])->name('bookings.store');
    Route::get('bookings/{booking}/edit', [BookingController::class, 'edit'])->name('bookings.edit');
    Route::put('bookings/{booking}', [BookingController::class, 'update'])->name('bookings.update');
    Route::delete('bookings/{booking}', [BookingController::class, 'destroy'])->name('bookings.destroy');
    Route::delete('bookings/{booking}/documents/{document}', [BookingController::class, 'destroyDocument'])->name('bookings.documents.destroy');

    // Settings: Rechnungsvorlagen
    Route::resource('invoice-templates', InvoiceTemplateController::class)->except('show');

    // Settings: Vertragsvorlagen
    Route::resource('contract-templates', ContractTemplateController::class)->except('show');
    Route::get('contract-templates/{contractTemplate}/data', [ContractTemplateController::class, 'data'])->name('contract-templates.data');

    // Settings: Kontoplan-Vorlagen
    Route::resource('chart-templates', ChartTemplateController::class);
    Route::post('chart-templates/{chartTemplate}/accounts', [ChartTemplateController::class, 'storeAccount'])->name('chart-templates.accounts.store');
    Route::put('chart-template-accounts/{account}', [ChartTemplateController::class, 'updateAccount'])->name('chart-templates.accounts.update');
    Route::delete('chart-template-accounts/{account}', [ChartTemplateController::class, 'destroyAccount'])->name('chart-templates.accounts.destroy');

    // Einstellungen
    Route::get('settings/profile', [SettingsController::class, 'profile'])->name('settings.profile');
    Route::patch('settings/profile', [SettingsController::class, 'updateProfile'])->name('settings.profile.update');
    Route::put('settings/password', [SettingsController::class, 'updatePassword'])->name('settings.password.update');
    Route::get('settings/appearance', [SettingsController::class, 'appearance'])->name('settings.appearance');
    Route::get('settings/system', [SettingsController::class, 'system'])->name('settings.system');

    // System (Logs, Changelog)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::view('changelog', 'admin.changelog')->name('changelog');

    // PLZ Lookup (by zip or city)
    Route::get('postal-codes/lookup', function (\Illuminate\Http\Request $request) {
        $zip = $request->input('zip', '');
        $city = $request->input('city', '');
        if (strlen($zip) < 4 && strlen($city) < 2) {
            return response()->json([]);
        }
        $file = public_path('data/postal-codes.json');
        if (!file_exists($file)) {
            return response()->json([]);
        }
        $data = json_decode(file_get_contents($file), true);
        $results = [];
        foreach ($data as $entry) {
            if ($zip && str_starts_with($entry['zip'], $zip)) {
                $results[] = $entry;
            } elseif ($city && stripos($entry['city'], $city) !== false) {
                $results[] = $entry;
            }
            if (count($results) >= 10) break;
        }
        return response()->json($results);
    })->name('postal-codes.lookup');
});

require __DIR__.'/auth.php';
