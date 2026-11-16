<?php

namespace App\Services;

use App\Models\Artwork;
use App\Models\ArtworkLogo;
use App\Models\Photo;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Confidential documents on the private "documents" disk
 * (storage/app/private/documents): contracts and their PDFs, contract
 * templates, invoices and their PDFs, QR codes (IBAN and amount), receipts
 * of bookings, documents of organizations and tasks, the documents module.
 *
 * Not here, and public on purpose: photos, artworks and logos, covers,
 * avatars, branding. The documents list also shows artworks, logos and
 * photos - those rows point at the public image and stay with it.
 */
class DocumentStorage extends PrivateStorage
{
    public const DISK = 'documents';

    /** Document rows that only mirror a public image. */
    public const PUBLIC_MIRRORS = [Artwork::class, ArtworkLogo::class, Photo::class];

    /** Folders that only ever held confidential uploads - moved whole, orphans included. */
    private const FOLDERS = ['bookings', 'contracts', 'contract-templates', 'documents', 'organizations', 'invoices'];

    /** ... except for these: the logos printed on contracts are no secret. */
    private const STAY_PUBLIC = ['contracts/logos/'];

    /** @return int how many files were moved */
    public static function moveOutOfPublic(): int
    {
        $public = Storage::disk('public');

        $folders = collect(self::FOLDERS)
            ->flatMap(fn ($folder) => $public->allFiles($folder))
            ->reject(fn ($path) => Str::startsWith($path, self::STAY_PUBLIC));

        // Soft-deleted documents too: their file is just as confidential
        $documents = static::column('documents', 'file_path', fn ($query) => $query->where(fn ($q) => $q
            ->whereNull('documentable_type')
            ->orWhereNotIn('documentable_type', self::PUBLIC_MIRRORS)));

        return static::moveAll($folders
            ->merge($documents)
            ->merge(static::column('invoices', 'qr_code_path'))
            ->merge(static::column('invoice_templates', 'qr_code_path')));
    }
}
