<?php

namespace Tests\Feature\Smoke;

use App\Models\Artwork;
use App\Models\Contract;
use App\Models\Document;
use App\Models\Invoice;
use App\Models\Organization;
use App\Models\Photo;
use App\Models\User;
use App\Services\DocumentStorage;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Contracts, invoices, QR codes, receipts and other confidential documents
 * lie on the private "documents" disk and reach logged-in users only.
 * Photos, artworks, logos and covers stay public.
 */
class DocumentPrivacyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
        Storage::fake('documents');
    }

    private function login(): static
    {
        return $this->actingAs(User::factory()->create());
    }

    private function contractDocument(string $path = 'contracts/vertrag.pdf'): Document
    {
        Storage::disk('documents')->put($path, '%PDF-1.4 vertrag');
        $contract = Contract::create(['title' => 'Labeldeal', 'type' => 'other', 'status' => 'draft']);

        return $contract->documents()->create(['title' => 'Vertrag Müller', 'category' => 'contract', 'file_path' => $path, 'mime_type' => 'application/pdf']);
    }

    public function test_the_documents_disk_lies_outside_the_web_root_and_has_no_url(): void
    {
        $root = str_replace('\\', '/', config('filesystems.disks.documents.root'));

        $this->assertStringStartsWith(str_replace('\\', '/', storage_path('app/private')), $root);
        $this->assertArrayNotHasKey('url', config('filesystems.disks.documents'));
    }

    public function test_without_login_there_is_no_document(): void
    {
        $document = $this->contractDocument();

        $this->get(route('admin.documents.download', $document))->assertRedirect('/login');
        $this->get(route('admin.documents.preview', $document))->assertRedirect('/login');
    }

    public function test_logged_in_documents_come_without_any_public_caching(): void
    {
        $document = $this->contractDocument();
        $this->login();

        $download = $this->get(route('admin.documents.download', $document))->assertOk();
        $this->assertStringContainsString('attachment', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('Vertrag Mu', $download->headers->get('Content-Disposition'), 'The title, with an ASCII fallback.');
        $this->assertStringContainsString('.pdf', $download->headers->get('Content-Disposition'));
        $this->assertStringContainsString('no-store', $download->headers->get('Cache-Control'));
        $this->assertStringNotContainsString('public', $download->headers->get('Cache-Control'));

        $preview = $this->get(route('admin.documents.preview', $document))->assertOk();
        $this->assertStringContainsString('inline', $preview->headers->get('Content-Disposition'));
        $this->assertSame('application/pdf', $preview->headers->get('Content-Type'));
    }

    public function test_uploads_go_to_the_private_disk(): void
    {
        $this->login()->post('/admin/documents', [
            'title' => 'Steuererklärung',
            'category' => 'legal',
            'file' => UploadedFile::fake()->create('steuern.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $path = Document::where('title', 'Steuererklärung')->value('file_path');
        Storage::disk('documents')->assertExists($path);
        Storage::disk('public')->assertMissing($path);
    }

    public function test_organization_documents_go_to_the_private_disk(): void
    {
        $organization = Organization::create(['type' => 'label', 'names' => ['Lawless Daydreamer']]);

        $this->login()->put("/admin/organizations/{$organization->id}", [
            'type' => 'label',
            'names' => ['Lawless Daydreamer'],
            'document' => UploadedFile::fake()->create('handelsregister.pdf', 20, 'application/pdf'),
        ])->assertRedirect();

        $path = $organization->documents()->value('file_path');
        $this->assertNotNull($path);
        Storage::disk('documents')->assertExists($path);
    }

    public function test_a_qr_code_is_served_only_to_logged_in_users(): void
    {
        Storage::disk('documents')->put('invoices/qr/zahlung.png', 'PNG');
        $invoice = Invoice::create(['invoice_number' => 'R-2026-001', 'type' => 'incoming', 'amount' => 100, 'invoice_date' => '2026-11-16', 'qr_code_path' => 'invoices/qr/zahlung.png']);

        $this->get(route('admin.invoices.qrCode', $invoice))->assertRedirect('/login');

        $response = $this->login()->get(route('admin.invoices.qrCode', $invoice))->assertOk();
        $this->assertStringNotContainsString('public', $response->headers->get('Cache-Control'));
    }

    /** Archived contract PDFs carry contract number and time in their name - not to be guessed from outside. */
    public function test_an_archived_contract_pdf_goes_to_the_private_disk(): void
    {
        $contract = Contract::create(['title' => 'Labeldeal', 'type' => 'other', 'status' => 'draft', 'language' => 'de']);

        $this->login()->post("/admin/contracts/{$contract->id}/pdf", ['archive' => 2])->assertRedirect();

        $document = $contract->documents()->firstOrFail();
        $this->assertStringStartsWith('contracts/archived/', $document->file_path);
        $this->assertStringStartsWith('%PDF-', Storage::disk('documents')->get($document->file_path));
        Storage::disk('public')->assertMissing($document->file_path);
    }

    /** Invoice PDFs are named after the invoice number - easy to guess, so never public. */
    public function test_an_invoice_pdf_goes_to_the_private_disk(): void
    {
        $invoice = Invoice::create(['invoice_number' => 'R-2026-007', 'type' => 'outgoing', 'amount' => 250, 'currency' => 'EUR', 'invoice_date' => '2026-11-16']);

        $response = $this->login()->get(route('admin.invoices.pdf', $invoice))->assertOk();

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));
        $this->assertStringStartsWith('%PDF-', Storage::disk('documents')->get('documents/invoices/Rechnung-R-2026-007.pdf'));
        Storage::disk('public')->assertMissing('documents/invoices/Rechnung-R-2026-007.pdf');
    }

    public function test_artwork_and_photo_entries_stay_with_their_public_image(): void
    {
        Storage::disk('public')->put('artworks/cover.jpg', 'JPG');
        $artwork = Artwork::create(['title' => 'Cover']);
        $mirror = Document::create(['title' => 'Cover — Artwork', 'category' => 'music', 'file_path' => 'artworks/cover.jpg', 'documentable_type' => Artwork::class, 'documentable_id' => $artwork->id]);

        $this->assertFalse($mirror->isConfidential());
        $this->login()->get(route('admin.documents.preview', $mirror))->assertOk();

        Storage::disk('public')->assertExists('artworks/cover.jpg');
        Storage::disk('documents')->assertMissing('artworks/cover.jpg');
    }

    public function test_a_confidential_file_still_on_the_public_disk_moves_when_asked_for(): void
    {
        Storage::disk('public')->put('bookings/beleg.pdf', 'BELEG');
        $document = Document::create(['title' => 'Beleg', 'category' => 'other', 'file_path' => 'bookings/beleg.pdf']);

        $this->login()->get(route('admin.documents.download', $document))->assertOk();

        Storage::disk('public')->assertMissing('bookings/beleg.pdf');
        $this->assertSame('BELEG', Storage::disk('documents')->get('bookings/beleg.pdf'));
    }

    public function test_the_move_takes_every_confidential_file_and_leaves_the_public_ones(): void
    {
        $public = Storage::disk('public');
        $confidential = [
            'contracts/vertrag.pdf', 'contracts/archived/V-001_20261116_101500.pdf', 'contract-templates/anhang.pdf',
            'documents/invoices/Rechnung-2026-001.pdf', 'documents/tasks/notiz.pdf', 'documents/allgemein.pdf',
            'bookings/beleg.pdf', 'organizations/hr.pdf', 'invoices/qr/zahlung.png',
            'invoice-templates/qr-vorlage.png',  // a template's QR code, by its column
            'organizations/verwaist.pdf',         // an orphan: no record points at it any more
        ];
        $stayPublic = [
            'contracts/logos/tyl.png', 'invoice-templates/logo.png', 'artworks/cover.jpg', 'artwork-logos/logo.png',
            'photos/tour/bild.jpg', 'covers/album.jpg', 'avatars/organizations/a.png', 'branding/favicon.png',
        ];
        foreach (array_merge($confidential, $stayPublic) as $path) {
            $public->put($path, "inhalt {$path}");
        }

        // Records: a soft-deleted contract document, artwork and photo mirrors, a template's QR code
        $deleted = Document::create(['title' => 'Alt', 'category' => 'contract', 'file_path' => 'contracts/vertrag.pdf']);
        $deleted->delete();
        Document::create(['title' => 'Cover', 'category' => 'music', 'file_path' => 'artworks/cover.jpg', 'documentable_type' => Artwork::class, 'documentable_id' => 1]);
        Document::create(['title' => 'Bild', 'category' => 'photo', 'file_path' => 'photos/tour/bild.jpg', 'documentable_type' => Photo::class, 'documentable_id' => 1]);
        DB::table('invoice_templates')->insert([
            'name' => 'Standard', 'slug' => 'standard',
            'logo_path' => 'invoice-templates/logo.png', 'qr_code_path' => 'invoice-templates/qr-vorlage.png', 'created_at' => now(), 'updated_at' => now(),
        ]);

        $this->assertSame(count($confidential), DocumentStorage::moveOutOfPublic());
        $this->assertSame(0, DocumentStorage::moveOutOfPublic(), 'Nothing left to move.');

        foreach ($confidential as $path) {
            $public->assertMissing($path);
            $this->assertSame("inhalt {$path}", Storage::disk('documents')->get($path), "{$path} was lost.");
        }
        foreach ($stayPublic as $path) {
            $public->assertExists($path);
        }
    }

    public function test_robots_are_kept_out(): void
    {
        $this->assertStringContainsString('Disallow: /', file_get_contents(public_path('robots.txt')));
        $this->get('/login')->assertSee('<meta name="robots" content="noindex, nofollow">', false);
        $this->login()->get('/admin/documents')->assertOk()->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }
}
