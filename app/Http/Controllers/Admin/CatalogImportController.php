<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\CatalogImport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * The one-time catalogue import (see CatalogImport): upload the prepared
 * file, look at what it would do against the live data, then run it.
 *
 * The hosting has no SSH, so this page stands in for an artisan command.
 * Admins only. Goes again once the catalogue is in.
 */
class CatalogImportController extends Controller
{
    private const FILE = 'catalog-import/pending.json';

    public function show()
    {
        $this->authorizeAdmin();

        $plan = null;
        $meta = null;
        $token = null;
        $fileError = null;

        if ($json = $this->pendingFile()) {
            try {
                $import = CatalogImport::fromJson($json);
                $plan = $import->plan();
                $meta = $import->meta();
                $token = sha1($json);
            } catch (\InvalidArgumentException $e) {
                $fileError = $e->getMessage();
            }
        }

        return view('admin.catalog-import.show', compact('plan', 'meta', 'token', 'fileError'));
    }

    public function upload(Request $request)
    {
        $this->authorizeAdmin();

        $request->validate(['file' => 'required|file|max:5120']);

        $json = (string) file_get_contents($request->file('file')->getRealPath());

        try {
            CatalogImport::fromJson($json);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('admin.catalog-import.show')->with('error', $e->getMessage());
        }

        Storage::disk('local')->put(self::FILE, $json);

        return redirect()->route('admin.catalog-import.show');
    }

    public function run(Request $request)
    {
        $this->authorizeAdmin();

        $json = $this->pendingFile();
        if ($json === null) {
            return redirect()->route('admin.catalog-import.show')->with('error', 'Es ist keine Importdatei hochgeladen.');
        }

        // Only the file that was looked at in the preview
        if (!hash_equals(sha1($json), (string) $request->input('token'))) {
            return redirect()->route('admin.catalog-import.show')
                ->with('error', 'Die Importdatei hat sich seit der Vorschau geändert. Bitte die Vorschau nochmals prüfen.');
        }

        $lock = Cache::lock('catalog-import', 600);
        if (!$lock->get()) {
            return redirect()->route('admin.catalog-import.show')->with('error', 'Der Import läuft bereits.');
        }

        try {
            @set_time_limit(300);
            $result = CatalogImport::fromJson($json)->run();
        } catch (\Throwable $e) {
            Log::error('Katalog-Import abgebrochen', ['user_id' => auth()->id(), 'error' => $e->getMessage()]);

            return redirect()->route('admin.catalog-import.show')
                ->with('error', 'Import abgebrochen, es wurde nichts gespeichert: ' . $e->getMessage());
        } finally {
            $lock->release();
        }

        Storage::disk('local')->delete(self::FILE);
        Log::info('Katalog-Import ausgefuehrt', ['user_id' => auth()->id()] + $result);

        return redirect()->route('admin.catalog-import.show')
            ->with('success', 'Katalog importiert.')
            ->with('importResult', $result);
    }

    public function discard()
    {
        $this->authorizeAdmin();

        Storage::disk('local')->delete(self::FILE);

        return redirect()->route('admin.catalog-import.show')->with('success', 'Importdatei verworfen.');
    }

    private function pendingFile(): ?string
    {
        $disk = Storage::disk('local');

        return $disk->exists(self::FILE) ? (string) $disk->get(self::FILE) : null;
    }

    private function authorizeAdmin(): void
    {
        abort_unless(auth()->user()?->isAdmin(), 403);
    }
}
