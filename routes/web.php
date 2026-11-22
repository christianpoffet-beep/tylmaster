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
use App\Http\Controllers\Admin\AnnualReportController;
use App\Http\Controllers\Admin\WeblingImportController;
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
use App\Http\Controllers\Admin\BankImportController;
use App\Http\Controllers\Admin\RoyaltyImportController;
use App\Http\Controllers\Admin\RoyaltyContractController;
use App\Http\Controllers\Admin\RoyaltyPaymentController;
use App\Http\Controllers\Admin\RoyaltyStatementController;
use App\Http\Controllers\Admin\BookingController;
use App\Http\Controllers\Admin\ContractTemplateController;
use App\Http\Controllers\Admin\ContractTypeController;
use App\Http\Controllers\Admin\InvoiceTemplateController;
use App\Http\Controllers\Admin\ActivityLogController;
use App\Http\Controllers\Admin\AddressCircleController;
use App\Http\Controllers\Admin\DeployController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\CampaignTemplateController;
use App\Http\Controllers\Admin\ContentPostController;
use App\Http\Controllers\ContentPreviewController;
use App\Http\Controllers\Admin\SettingsController;
use App\Http\Controllers\Admin\MusicSettingsController;
use App\Http\Controllers\Admin\ProductTemplateController;
use App\Http\Controllers\PublicGalleryController;
use Illuminate\Support\Facades\Route;

// Public photo/gallery routes (no auth)
Route::get('dl/{path}', [PublicGalleryController::class, 'downloadPhoto'])->where('path', '.*');
Route::get('p/{path}', [PublicGalleryController::class, 'showPhoto'])->where('path', '.*');
Route::get('gallery/{token}/download', [PublicGalleryController::class, 'downloadFolder']);
Route::get('gallery/{token}', [PublicGalleryController::class, 'showGallery']);

// Public content post preview (no auth)
Route::get('preview/content/{token}', [ContentPreviewController::class, 'show'])->name('content-preview.show');

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
    Route::get('contacts-export', [ContactController::class, 'export'])->name('contacts.export');
    Route::resource('organizations', OrganizationController::class);
    Route::get('organizations-export', [OrganizationController::class, 'export'])->name('organizations.export');
    Route::get('organizations-search', [OrganizationController::class, 'search'])->name('organizations.search');
    Route::post('organizations-quick', [OrganizationController::class, 'storeQuick'])->name('organizations.storeQuick');
    Route::resource('genres', GenreController::class)->except('show');
    Route::resource('organization-types', OrganizationTypeController::class)->except('show');
    Route::resource('contact-types', ContactTypeController::class)->except('show');
    Route::resource('project-types', ProjectTypeController::class)->except('show');
    Route::resource('contract-types', ContractTypeController::class)->except('show');
    // Deleting is allowed for drafts only; ContractController::destroy checks the status.
    Route::resource('contracts', ContractController::class);
    Route::patch('contracts/{contract}/documents/{document}/archive', [ContractController::class, 'archiveDocument'])->name('contracts.documents.archive');
    Route::post('contracts/{contract}/pdf', [ContractController::class, 'pdf'])->name('contracts.pdf');
    Route::post('contracts/{contract}/advances', [ContractController::class, 'storeAdvance'])->name('contracts.advances.store');
    Route::delete('contract-advances/{contractAdvance}', [ContractController::class, 'destroyAdvance'])->name('contracts.advances.destroy');
    Route::resource('documents', DocumentController::class)->only(['index', 'create', 'store', 'show', 'destroy']);
    Route::get('documents/{document}/download', [DocumentController::class, 'download'])->name('documents.download');
    Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->name('documents.preview');
    Route::resource('tracks', TrackController::class);
    Route::get('tracks/{track}/copy-metadata', [TrackController::class, 'copyMetadata'])->name('tracks.copyMetadata');
    Route::post('tracks/{track}/apply-metadata', [TrackController::class, 'applyMetadata'])->name('tracks.applyMetadata');
    Route::get('tracks/{track}/download', [TrackController::class, 'download'])->name('tracks.download');
    Route::get('tracks/{track}/audio-files/{audioFile}/download', [TrackController::class, 'downloadAudioFile'])->name('tracks.audioFiles.download');
    Route::get('tracks/{track}/audio-files/{audioFile}/stream', [TrackController::class, 'streamAudioFile'])->name('tracks.audioFiles.stream');
    Route::post('tracks/bulk-update', [TrackController::class, 'bulkUpdate'])->name('tracks.bulkUpdate');
    Route::resource('releases', ReleaseController::class);

    Route::resource('projects', ProjectController::class);
    Route::post('projects/{project}/tasks', [ProjectController::class, 'storeTask'])->name('projects.tasks.store');
    Route::patch('projects/{project}/tasks/{task}/toggle', [ProjectController::class, 'toggleTask'])->name('projects.tasks.toggle');
    Route::resource('artworks', ArtworkController::class);
    Route::get('artworks/{artwork}/thumbnail', [ArtworkController::class, 'thumbnail'])->name('artworks.thumbnail');
    Route::delete('artworks/{artwork}/images/{image}', [ArtworkController::class, 'destroyImage'])->name('artworks.images.destroy');
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
    Route::get('contracts-logo-search', [ContractController::class, 'logoSearch'])->name('contracts.logo-search');
    Route::get('tracks-search', [TrackController::class, 'search'])->name('tracks.search');
    Route::get('tracks-isrc/next', [TrackController::class, 'nextIsrc'])->name('tracks.nextIsrc');
    Route::get('tracks-isrc/check', [TrackController::class, 'checkIsrc'])->name('tracks.checkIsrc');
    Route::get('artworks-search', [ArtworkController::class, 'search'])->name('artworks.search');
    Route::get('releases-search', [ReleaseController::class, 'search'])->name('releases.search');
    Route::get('releases-next-catalog-number', [ReleaseController::class, 'nextCatalogNumber'])->name('releases.nextCatalogNumber');
    Route::get('submissions-search', [SubmissionController::class, 'search'])->name('submissions.search');
    Route::resource('tasks', TaskController::class);
    Route::patch('tasks/{task}/toggle', [TaskController::class, 'toggle'])->name('tasks.toggle');
    Route::delete('tasks/{task}/documents/{document}', [TaskController::class, 'destroyDocument'])->name('tasks.documents.destroy');
    Route::resource('invoices', InvoiceController::class);
    Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
    Route::get('invoices/{invoice}/qr-code', [InvoiceController::class, 'qrCode'])->name('invoices.qrCode');
    Route::get('invoice-templates/{invoiceTemplate}/qr-code', [InvoiceTemplateController::class, 'qrCode'])->name('invoice-templates.qrCode');
    Route::patch('invoices/{invoice}/paid', [InvoiceController::class, 'markPaid'])->name('invoices.markPaid');
    Route::get('invoice-templates/{invoiceTemplate}/data', [InvoiceController::class, 'templateData'])->name('invoice-templates.data');
    Route::get('organizations/{organization}/contacts', [InvoiceController::class, 'organizationContacts'])->name('organizations.contacts');
    Route::get('accountings/{accounting}/accounts', [InvoiceController::class, 'accountingAccounts'])->name('accountings.accounts');
    Route::resource('expenses', ExpenseController::class)->except('show');
    Route::delete('expenses/{expense}/documents/{document}', [ExpenseController::class, 'destroyDocument'])->name('expenses.documents.destroy');
    Route::get('submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('submissions/{submission}', [SubmissionController::class, 'show'])->name('submissions.show');
    Route::get('submissions/{submission}/file', [SubmissionController::class, 'file'])->name('submissions.file');
    Route::patch('submissions/{submission}/status', [SubmissionController::class, 'updateStatus'])->name('submissions.updateStatus');
    Route::post('submissions/{submission}/import', [SubmissionController::class, 'import'])->name('submissions.import');
    Route::delete('submissions/{submission}', [SubmissionController::class, 'destroy'])->name('submissions.destroy');

    // Buchhaltung
    // Before the resource: "import-webling" is no accounting id
    Route::get('accountings/import-webling', [WeblingImportController::class, 'create'])->name('accountings.importWebling');
    Route::post('accountings/import-webling', [WeblingImportController::class, 'store'])->name('accountings.importWebling.store');
    Route::resource('accountings', AccountingController::class);
    Route::patch('accountings/{accounting}/close', [AccountingController::class, 'close'])->name('accountings.close');
    Route::patch('accountings/{accounting}/reopen', [AccountingController::class, 'reopen'])->name('accountings.reopen');
    Route::post('accountings/{accounting}/refresh-openings', [AccountingController::class, 'refreshOpenings'])->name('accountings.refreshOpenings');
    Route::get('accountings/{accounting}/export', [AnnualReportController::class, 'show'])->name('accountings.export');
    Route::get('accountings/{accounting}/export/download', [AnnualReportController::class, 'download'])->name('accountings.export.download');
    Route::get('accountings/{accounting}/journal', [AccountingController::class, 'journal'])->name('accountings.journal');
    Route::get('accountings/{accounting}/ledger/{account}', [AccountingController::class, 'ledger'])->name('accountings.ledger');
    Route::get('accountings/{accounting}/trial-balance', [AccountingController::class, 'trialBalance'])->name('accountings.trialBalance');
    Route::get('accountings/{accounting}/balance-sheet', [AccountingController::class, 'balanceSheet'])->name('accountings.balanceSheet');
    Route::get('accountings/{accounting}/income-statement', [AccountingController::class, 'incomeStatement'])->name('accountings.incomeStatement');
    Route::post('accountings/{accounting}/accounts', [AccountingController::class, 'storeAccount'])->name('accountings.accounts.store');
    Route::put('accounting-accounts/{account}', [AccountingController::class, 'updateAccount'])->name('accountings.accounts.update');
    Route::delete('accounting-accounts/{account}', [AccountingController::class, 'destroyAccount'])->name('accountings.accounts.destroy');

    // Bankabgleich (Bankauszug einlesen und buchen)
    Route::get('accountings/{accounting}/bank', [BankImportController::class, 'index'])->name('accountings.bank');
    Route::post('accountings/{accounting}/bank/import', [BankImportController::class, 'import'])->name('accountings.bank.import');
    Route::post('accountings/{accounting}/bank/book', [BankImportController::class, 'book'])->name('accountings.bank.book');
    Route::post('accountings/{accounting}/bank/ignore', [BankImportController::class, 'ignore'])->name('accountings.bank.ignore');
    Route::patch('bank-transactions/{bankTransaction}/reopen', [BankImportController::class, 'reopen'])->name('bank-transactions.reopen');
    Route::get('bank-imports/{bankImport}/file', [BankImportController::class, 'file'])->name('bank-imports.file');
    Route::delete('bank-imports/{bankImport}', [BankImportController::class, 'destroyImport'])->name('bank-imports.destroy');
    Route::delete('booking-rules/{bookingRule}', [BankImportController::class, 'destroyRule'])->name('booking-rules.destroy');

    // Rebeat-Matcher (Nutzungsdaten einlesen und den Tracks zuordnen)
    Route::get('royalties', [RoyaltyImportController::class, 'index'])->name('royalties.index');
    Route::post('royalties/imports', [RoyaltyImportController::class, 'store'])->name('royalties.store');
    Route::get('royalties/uploads/token', [RoyaltyImportController::class, 'token'])->name('royalties.token');
    Route::post('royalties/uploads', [RoyaltyImportController::class, 'start'])->name('royalties.start');
    Route::post('royalties/uploads/{royaltyImport}/piece', [RoyaltyImportController::class, 'piece'])->name('royalties.piece');
    Route::post('royalties/uploads/{royaltyImport}/finish', [RoyaltyImportController::class, 'finish'])->name('royalties.finish');
    Route::post('royalties/rematch', [RoyaltyImportController::class, 'rematch'])->name('royalties.rematch');
    Route::get('royalties/payments', [RoyaltyPaymentController::class, 'index'])->name('royalties.payments.index');
    Route::post('royalties/payments', [RoyaltyPaymentController::class, 'store'])->name('royalties.payments.store');
    Route::post('royalties/payments/upload', [RoyaltyPaymentController::class, 'upload'])->name('royalties.payments.upload');
    Route::delete('royalties/payments/{royaltyPayment}', [RoyaltyPaymentController::class, 'destroy'])->name('royalties.payments.destroy');
    Route::get('royalties/statements', [RoyaltyStatementController::class, 'index'])->name('royalties.statements.index');
    Route::post('royalties/statements', [RoyaltyStatementController::class, 'store'])->name('royalties.statements.store');
    Route::get('royalties/statements/{royaltyStatement}', [RoyaltyStatementController::class, 'show'])->name('royalties.statements.show');
    Route::put('royalties/statements/{royaltyStatement}', [RoyaltyStatementController::class, 'update'])->name('royalties.statements.update');
    Route::get('royalties/statements/{royaltyStatement}/pdf/{party}', [RoyaltyStatementController::class, 'pdf'])->name('royalties.statements.pdf');
    Route::get('royalties/statements/{royaltyStatement}/pdfs', [RoyaltyStatementController::class, 'pdfs'])->name('royalties.statements.pdfs');
    Route::post('royalties/statements/{royaltyStatement}/finalize', [RoyaltyStatementController::class, 'finalize'])->name('royalties.statements.finalize');
    Route::post('royalties/statements/{royaltyStatement}/undo', [RoyaltyStatementController::class, 'undo'])->name('royalties.statements.undo');
    Route::delete('royalties/statements/{royaltyStatement}', [RoyaltyStatementController::class, 'destroy'])->name('royalties.statements.destroy');
    Route::get('royalties/contracts', [RoyaltyContractController::class, 'index'])->name('royalties.contracts');
    Route::post('royalties/contracts/own-label', [RoyaltyContractController::class, 'setOwnLabel'])->name('royalties.ownLabel');
    Route::get('royalties/imports/{royaltyImport}', [RoyaltyImportController::class, 'show'])->name('royalties.show');
    Route::post('royalties/imports/{royaltyImport}/assign', [RoyaltyImportController::class, 'assign'])->name('royalties.assign');
    Route::post('royalties/imports/{royaltyImport}/create-track', [RoyaltyImportController::class, 'createTrack'])->name('royalties.createTrack');
    Route::patch('royalties/imports/{royaltyImport}/confirm', [RoyaltyImportController::class, 'confirm'])->name('royalties.confirm');
    Route::get('royalties/imports/{royaltyImport}/file', [RoyaltyImportController::class, 'file'])->name('royalties.file');
    Route::delete('royalties/imports/{royaltyImport}', [RoyaltyImportController::class, 'destroy'])->name('royalties.destroy');

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
    Route::patch('contract-templates/{contractTemplate}/documents/{document}/archive', [ContractTemplateController::class, 'archiveDocument'])->name('contract-templates.documents.archive');

    // Settings: Kampagnenvorlagen
    Route::resource('campaign-templates', CampaignTemplateController::class)->except('show');
    Route::get('campaign-templates/{campaignTemplate}/data', [CampaignTemplateController::class, 'data'])->name('campaign-templates.data');

    // Settings: Produktvorlagen
    Route::resource('product-templates', ProductTemplateController::class)->except('show');
    Route::get('product-templates/{productTemplate}/data', [ProductTemplateController::class, 'data'])->name('product-templates.data');

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
    Route::post('settings/system', [SettingsController::class, 'updateSystem'])->name('settings.system.update');

    // Deploy helper: runs migrations and clears caches (no SSH on this hosting)
    Route::post('deploy-refresh', [DeployController::class, 'refresh'])->name('deploy-refresh');

    // Musik-Settings
    Route::get('settings/credit-roles', [MusicSettingsController::class, 'creditRoles'])->name('settings.credit-roles');
    Route::post('settings/credit-roles', [MusicSettingsController::class, 'updateCreditRoles'])->name('settings.credit-roles.update');
    Route::get('settings/instruments', [MusicSettingsController::class, 'instruments'])->name('settings.instruments');
    Route::post('settings/instruments', [MusicSettingsController::class, 'updateInstruments'])->name('settings.instruments.update');
    Route::get('settings/product-types', [MusicSettingsController::class, 'productTypes'])->name('settings.product-types');
    Route::post('settings/product-types', [MusicSettingsController::class, 'updateProductTypes'])->name('settings.product-types.update');

    // System (Logs, Changelog)
    Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    Route::get('activity-logs/export', [ActivityLogController::class, 'export'])->name('activity-logs.export');
    Route::view('changelog', 'admin.changelog')->name('changelog');

    // Kampagnen
    Route::resource('campaigns', CampaignController::class);
    Route::post('campaigns/{campaign}/duplicate', [CampaignController::class, 'duplicate'])->name('campaigns.duplicate');
    Route::post('campaigns/{campaign}/send', [CampaignController::class, 'send'])->name('campaigns.send');
    Route::post('campaigns/{campaign}/test-send', [CampaignController::class, 'testSend'])->name('campaigns.testSend');
    Route::get('campaigns/{campaign}/send-log', [CampaignController::class, 'sendLog'])->name('campaigns.sendLog');

    // Content-Planer
    Route::resource('content-posts', ContentPostController::class);
    Route::post('content-posts/{content_post}/duplicate', [ContentPostController::class, 'duplicate'])->name('content-posts.duplicate');
    Route::patch('content-posts/{content_post}/mark-published', [ContentPostController::class, 'markPublished'])->name('content-posts.markPublished');
    Route::post('content-posts/{content_post}/share', [ContentPostController::class, 'share'])->name('content-posts.share');
    Route::delete('content-posts/{content_post}/share', [ContentPostController::class, 'unshare'])->name('content-posts.unshare');
    Route::get('content-posts-image-browser', [ContentPostController::class, 'imageBrowser'])->name('content-posts.image-browser');
    Route::delete('content-posts/{content_post}/images/{image}', [ContentPostController::class, 'removeImage'])->name('content-posts.images.destroy');
    Route::post('content-posts/{content_post}/images/reorder', [ContentPostController::class, 'reorderImages'])->name('content-posts.images.reorder');

    // Kampagnen: Adresskreise
    Route::resource('address-circles', AddressCircleController::class);
    Route::post('address-circles/{address_circle}/filter', [AddressCircleController::class, 'filter'])->name('address-circles.filter');
    Route::post('address-circles/{address_circle}/add-members', [AddressCircleController::class, 'addMembers'])->name('address-circles.add-members');
    Route::post('address-circles/{address_circle}/remove-members', [AddressCircleController::class, 'removeMembers'])->name('address-circles.remove-members');
    Route::post('address-circles/{address_circle}/update-member-email', [AddressCircleController::class, 'updateMemberEmail'])->name('address-circles.update-member-email');
    Route::get('address-circles/{address_circle}/export', [AddressCircleController::class, 'export'])->name('address-circles.export');

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
