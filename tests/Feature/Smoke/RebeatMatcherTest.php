<?php

namespace Tests\Feature\Smoke;

use App\Models\Organization;
use App\Models\Release;
use App\Models\RoyaltyImport;
use App\Models\RoyaltyLine;
use App\Models\RoyaltyMatchRule;
use App\Models\Track;
use App\Models\User;
use App\Services\Royalties\RebeatImporter;
use App\Services\Royalties\RebeatReader;
use App\Services\Royalties\RoyaltyImportException;
use App\Services\Royalties\RoyaltyMatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The Rebeat-Matcher: Rebeat's accounting export is read, its lines go to the
 * tracks by ISRC, the rest is assigned by hand and remembered. Made-up lines
 * in Rebeat's layout.
 */
class RebeatMatcherTest extends TestCase
{
    use RefreshDatabase;

    private const HEADER = 'id;_artist;_ean;_isrc;_title;_salesdate;_periode;_periodeabr;_country;_count;_totalsum;_cash;_society;_shop;_cline;LastEditID;CreationDate;_salestype;LastEditIDDaily;_portal;_postalcode;_id;_eanoriginal;_streamtype;_salescategory';

    private array $files = [];
    private Track $track;
    private Organization $band;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('documents');
        $this->band = Organization::create(['type' => 'band', 'names' => ['Testband']]);
        $this->track = Track::create(['title' => 'Erster Song', 'isrc' => 'CHTYL2300001', 'status' => 'released']);
        $this->track->organizations()->attach($this->band);
    }

    protected function tearDown(): void
    {
        foreach ($this->files as $file) {
            @unlink($file);
        }
        parent::tearDown();
    }

    /** One line as Rebeat writes it: [id, artist, isrc, title, month (Y-m), country, count, cash, shop, label, type, category, abo] */
    private function line(string $id, string $artist, string $isrc, string $title, string $month, float $cash, array $extra = []): string
    {
        [$y, $m] = explode('-', $month);
        $last = date('t', mktime(0, 0, 0, (int) $m, 1, (int) $y));
        $e = $extra + ['country' => 'SWITZERLAND', 'count' => 1, 'shop' => 'Spotify', 'label' => 'The yelling light',
            'type' => 'Stream', 'category' => 'Track', 'abo' => 'P', 'ean' => ''];

        return implode(';', [$id, $artist, $e['ean'], $isrc, $title, "{$last}.{$m}.{$y} 00:00", "01.{$m}.{$y} 00:00", "01.{$m}.{$y} 00:00",
            $e['country'], $e['count'], number_format($cash / 0.85, 6, '.', ''), number_format($cash, 6, '.', ''), '0', $e['shop'], $e['label'],
            '123', '', $e['type'], '', $e['shop'], '', '1', '9.01E+12', $e['abo'], $e['category'] . '     ']);
    }

    private function export(array $lines): string
    {
        $path = tempnam(sys_get_temp_dir(), 'rebeat') . '.csv';
        file_put_contents($path, "\xEF\xBB\xBFAll amounts are in EUR;;;;;;;;;;;;;;;;;;;;;;;;\r\n;;;;;;;;;;;;;;;;;;;;;;;;\r\n"
            . self::HEADER . "\r\n" . implode("\r\n", $lines) . "\r\n");

        return $this->files[] = $path;
    }

    private function standardExport(): string
    {
        return $this->export([
            $this->line('a-1', 'Testband', 'CHTYL2300001', 'Erster Song', '2023-01', 0.50),
            $this->line('a-2', 'Testband', 'CHTYL2300001', 'Erster Song', '2023-02', 0.25, ['country' => 'GERMANY', 'shop' => 'iTunesAppleMusic', 'abo' => 'Family Monthly PAID']),
            $this->line('a-3', 'Testband', 'CHTYL2300002', 'Zweiter Song', '2023-02', 1.00, ['count' => 3]),
            $this->line('a-4', 'Testband', '', 'Das Album', '2023-03', 5.00, ['type' => 'Download', 'category' => 'Album', 'shop' => 'iTunesDownload']),
            $this->line('a-5', 'Anderes Label Band', 'CHXYZ2300009', 'Fremder Song', '2023-03', 0.10, ['label' => 'Zentrum']),
            $this->line('a-6', 'Testband', 'CHTYL2300001', 'Erster Song', '2022-12', 0.40),
        ]);
    }

    public function test_the_reader_understands_rebeats_layout(): void
    {
        $rows = iterator_to_array((new RebeatReader($this->standardExport()))->rows(), false);

        $this->assertCount(6, $rows);
        $first = $rows[0];
        $this->assertSame(['a-1', 'CHTYL2300001', 'Testband', 'Erster Song', 'The yelling light'], [$first['external_id'], $first['isrc'], $first['artist'], $first['title'], $first['label']]);
        $this->assertSame(['2023-01-01', '2023-01-31', 'SWITZERLAND', 'Spotify', 'Stream', 'Track', 'P'],
            [$first['period'], $first['sales_date'], $first['country'], $first['shop'], $first['sales_type'], $first['sales_category'], $first['stream_type']]);
        $this->assertSame([0.5, 0.588235], [$first['amount'], $first['amount_gross']]);
        $this->assertNull($first['ean'], 'Excel\'s "9.01E+12" is no EAN.');
        $this->assertNull($rows[3]['isrc'], 'Album downloads come without ISRC.');
        $this->assertSame(3, $rows[2]['quantity']);
    }

    public function test_another_file_is_refused(): void
    {
        $path = $this->files[] = tempnam(sys_get_temp_dir(), 'other') . '.csv';
        file_put_contents($path, "IBAN;Booked At;Text;Credit/Debit Amount\nCH00;2025-01-01;Test;10\n");

        $this->expectException(RoyaltyImportException::class);
        new RebeatReader($path);
    }

    public function test_lines_go_to_the_tracks_by_isrc_also_of_an_audio_version(): void
    {
        $second = Track::create(['title' => 'Zweiter Song', 'status' => 'released']);
        $second->audioFiles()->create(['file_path' => 'x.wav', 'original_name' => 'x.wav', 'isrc' => 'CH-TYL-23-00002', 'is_main' => true]);

        $import = app(RebeatImporter::class)->import($this->standardExport(), 'AccountingDataExport.csv');

        $this->assertSame([6, 0, 1], [$import->rows_new, $import->rows_duplicate, $import->rows_presettled]);
        $this->assertSame(['2022-12-01', '2023-03-01'], [$import->period_from->toDateString(), $import->period_to->toDateString()]);
        $this->assertSame(3, $this->track->royaltyLines()->count(), 'Two months of 2023 and one of 2022');
        $this->assertSame(1, $second->royaltyLines()->count(), 'Found by the ISRC of its audio version');
        $this->assertSame(RoyaltyLine::MATCH_ISRC, $second->royaltyLines()->value('match_type'));
        $this->assertSame(2, RoyaltyLine::whereNull('track_id')->whereNull('release_id')->count(), 'The album and the other label\'s song stay open');
        $this->assertTrue(RoyaltyLine::where('external_id', 'a-6')->value('presettled'), 'Settled by hand before 2023');
    }

    public function test_an_overlapping_export_only_adds_what_is_new(): void
    {
        app(RebeatImporter::class)->import($this->standardExport(), 'first.csv');
        $second = app(RebeatImporter::class)->import($this->export([
            $this->line('a-1', 'Testband', 'CHTYL2300001', 'Erster Song', '2023-01', 0.50),
            $this->line('b-1', 'Testband', 'CHTYL2300001', 'Erster Song', '2023-04', 0.70),
        ]), 'second.csv');

        $this->assertSame([1, 1], [$second->rows_new, $second->rows_duplicate]);
        $this->assertSame(7, RoyaltyLine::count());
    }

    public function test_an_assignment_by_hand_is_remembered_for_the_next_import(): void
    {
        $matcher = app(RoyaltyMatcher::class);
        $import = app(RebeatImporter::class)->import($this->standardExport(), 'first.csv');
        $other = Track::create(['title' => 'Fremder Song', 'status' => 'released']);
        $album = Release::create(['title' => 'Das Album']);

        $this->assertSame(1, $matcher->assign('rebeat', 'CHXYZ2300009', null, null, $other->id, null));
        $this->assertSame(1, $matcher->assign('rebeat', null, 'Testband', 'Das Album', null, $album->id));
        $this->assertSame(['CHTYL2300002'], RoyaltyLine::whereNull('track_id')->whereNull('release_id')->pluck('isrc')->all(),
            'Only "Zweiter Song" is left - there is no such track here');
        $this->assertSame(2, RoyaltyMatchRule::count());

        $next = app(RebeatImporter::class)->import($this->export([
            $this->line('c-1', 'Anderes Label Band', 'CHXYZ2300009', 'Fremder Song', '2023-05', 0.20, ['label' => 'Zentrum']),
            $this->line('c-2', 'Testband', '', 'Das Album', '2023-05', 4.00, ['type' => 'Download', 'category' => 'Album']),
        ]), 'second.csv');

        $this->assertSame([$other->id, RoyaltyLine::MATCH_RULE], [RoyaltyLine::where('external_id', 'c-1')->value('track_id'), RoyaltyLine::where('external_id', 'c-1')->value('match_type')]);
        $this->assertSame($album->id, RoyaltyLine::where('external_id', 'c-2')->value('release_id'));

        // Released again: the rule goes, the lines are open again
        $matcher->assign('rebeat', 'CHXYZ2300009', null, null, null, null);
        $this->assertSame(0, RoyaltyMatchRule::where('match_key', 'isrc:CHXYZ2300009')->count());
        $this->assertSame(2, RoyaltyLine::where('isrc', 'CHXYZ2300009')->whereNull('track_id')->count());
        $this->assertNotNull($next->fresh());
    }

    public function test_a_missing_track_is_created_with_its_band_and_label(): void
    {
        $band = Organization::create(['type' => 'band', 'names' => ['Anderes Label Band']]);
        $label = Organization::create(['type' => 'label', 'names' => ['Zentrum']]);
        app(RebeatImporter::class)->import($this->standardExport(), 'first.csv');

        $track = app(RoyaltyMatcher::class)->createTrack('rebeat', 'CHXYZ2300009', 'Anderes Label Band', 'Fremder Song', 'Zentrum');

        $this->assertSame(['Fremder Song', 'CHXYZ2300009'], [$track->title, $track->isrc]);
        $this->assertEqualsCanonicalizing([$band->id, $label->id], $track->organizations()->pluck('organizations.id')->all());
        $this->assertSame(1, $track->royaltyLines()->count());
    }

    public function test_suggestions_compare_the_titles(): void
    {
        Track::create(['title' => 'Fremder Song (Remastered)', 'status' => 'released']);
        Track::create(['title' => 'Ganz etwas anderes', 'status' => 'released']);

        $suggestions = app(RoyaltyMatcher::class)->suggestions('CHXYZ2300009', 'Anderes Label Band', 'Fremder Song');

        $this->assertSame(['Fremder Song (Remastered)'], array_column($suggestions, 'title'));
        $this->assertSame(100, RoyaltyMatcher::titleScore('Pre-Natal Cannibalism', 'pre natal cannibalism'));
    }

    public function test_the_pages_upload_confirm_and_remove_an_import(): void
    {
        $user = User::factory()->create();
        $this->actingAs($user)->get('/admin/royalties')->assertOk()->assertSee('Rebeat-Matcher')->assertSee('Noch nichts eingelesen');

        $response = $this->actingAs($user)->post('/admin/royalties/imports', [
            'file' => new UploadedFile($this->standardExport(), 'AccountingDataExport-2023.csv', null, null, true),
        ]);
        $import = RoyaltyImport::sole();
        $response->assertRedirect("/admin/royalties/imports/{$import->id}")->assertSessionHas('success');
        $this->assertNotNull($import->file_path);

        $this->actingAs($user)->get("/admin/royalties/imports/{$import->id}")->assertOk()
            ->assertSee('Ohne Track (3)')->assertSee('Fremder Song')->assertSee('Das Album')
            ->assertSee('Zugeordnet (1)')->assertSee('Import bestätigen');
        $this->actingAs($user)->get('/admin/royalties')->assertOk()->assertSee('AccountingDataExport-2023.csv')->assertSee('2023');

        // The same file again: nothing new, no second import
        $this->actingAs($user)->post('/admin/royalties/imports', [
            'file' => new UploadedFile($this->standardExport(), 'again.csv', null, null, true),
        ])->assertSessionHas('error');
        $this->assertSame(1, RoyaltyImport::count());

        $this->actingAs($user)->patch("/admin/royalties/imports/{$import->id}/confirm")->assertSessionHas('success');
        $this->assertNotNull($import->fresh()->confirmed_at);

        $this->actingAs($user)->post("/admin/royalties/imports/{$import->id}/assign", [
            'isrc' => 'CHXYZ2300009', 'track_id' => $this->track->id,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertSame(4, $this->track->royaltyLines()->count());

        $this->actingAs($user)->delete("/admin/royalties/imports/{$import->id}")->assertRedirect('/admin/royalties');
        $this->assertSame([0, 0], [RoyaltyImport::count(), RoyaltyLine::count()]);
        Storage::disk('documents')->assertMissing($import->file_path);
    }

    /** The page cuts the file at line ends: the first piece carries Rebeat's header, the next ones only lines. */
    private function pieces(): array
    {
        $lines = preg_split('/(?<=\n)/', file_get_contents($this->standardExport()), -1, PREG_SPLIT_NO_EMPTY);

        return [implode('', array_slice($lines, 0, 5)), implode('', array_slice($lines, 5))];
    }

    private function sendPiece(User $user, string $url, int $index, string $body, string $encoding = 'identity')
    {
        return $this->actingAs($user)->call('POST', $url, [], [], [], [
            'HTTP_X_PIECE_INDEX' => (string) $index, 'HTTP_X_PIECE_ENCODING' => $encoding,
            'CONTENT_TYPE' => 'application/octet-stream', 'HTTP_ACCEPT' => 'application/json',
        ], $body);
    }

    public function test_a_large_export_arrives_in_compressed_pieces(): void
    {
        $user = User::factory()->create();
        [$first, $second] = $this->pieces();

        $start = $this->actingAs($user)->postJson('/admin/royalties/uploads', ['file_name' => 'AccountingDataExport.csv'])->assertOk()->json();
        $this->assertStringStartsWith('/admin/royalties/uploads/', $start['piece'], 'Relative: posted to the host the page came from');

        // As the page sends it: a form post with the gzipped piece as a file
        $gz = tempnam(sys_get_temp_dir(), 'piece');
        file_put_contents($gz, gzencode($first));
        $this->files[] = $gz;
        $this->actingAs($user)->post($start['piece'], ['index' => '0', 'encoding' => 'gzip', 'piece' => new UploadedFile($gz, 'teil-1.gz', null, null, true)],
            ['Accept' => 'application/json'])->assertOk()->assertJson(['rows' => 2]);
        $this->sendPiece($user, $start['piece'], 0, gzencode($first), 'gzip')->assertStatus(409);
        // ... or as a raw body
        $this->sendPiece($user, $start['piece'], 1, $second)->assertOk()->assertJson(['rows' => 6]);

        $this->actingAs($user)->postJson($start['finish'])->assertOk()->assertJsonStructure(['redirect']);

        $import = RoyaltyImport::sole();
        $this->assertTrue($import->is_complete);
        $this->assertSame([6, 2], [$import->rows_new, $import->upload_pieces]);
        $this->assertSame($first . $second, Storage::disk('documents')->get($import->file_path), 'The whole file is kept, as uploaded');
        $this->assertSame(3, $this->track->royaltyLines()->count());
    }

    public function test_the_upload_can_fetch_a_fresh_token_and_learns_when_logged_out(): void
    {
        $this->actingAs(User::factory()->create())->getJson('/admin/royalties/uploads/token')->assertOk()->assertJsonStructure(['token']);
        auth()->logout();
        $this->getJson('/admin/royalties/uploads/token')->assertUnauthorized()->assertJsonMissing(['token']);
    }

    public function test_an_upload_that_broke_off_is_cleared_by_the_next_one(): void
    {
        $user = User::factory()->create();
        [$first] = $this->pieces();
        $start = $this->actingAs($user)->postJson('/admin/royalties/uploads', ['file_name' => 'AccountingDataExport.csv'])->json();
        $this->sendPiece($user, $start['piece'], 0, $first)->assertOk();

        $broken = RoyaltyImport::sole();
        $this->actingAs($user)->get("/admin/royalties/imports/{$broken->id}")->assertOk()->assertSee('unvollständig')->assertDontSee('Import bestätigen');
        $this->actingAs($user)->patch("/admin/royalties/imports/{$broken->id}/confirm")->assertSessionHas('error');

        $this->actingAs($user)->post('/admin/royalties/imports', [
            'file' => new UploadedFile($this->standardExport(), 'AccountingDataExport.csv', null, null, true),
        ])->assertSessionHas('success');

        $this->assertNull($broken->fresh(), 'The broken import is gone');
        $this->assertSame(6, RoyaltyImport::sole()->rows_new, 'Its lines do not count as "already there"');
    }

    public function test_a_piece_without_the_header_is_refused(): void
    {
        $user = User::factory()->create();
        [, $second] = $this->pieces();
        $start = $this->actingAs($user)->postJson('/admin/royalties/uploads', ['file_name' => 'AccountingDataExport.csv'])->json();

        $this->sendPiece($user, $start['piece'], 0, $second)->assertStatus(422)->assertJsonFragment(['message' => 'Das ist kein Rebeat-Export. Erwartet wird die CSV-Datei aus Rebeat → Accounting → Export («AccountingDataExport-…csv»).']);
        $this->assertSame(0, RoyaltyImport::count());
    }

    public function test_a_form_too_large_for_php_says_so_instead_of_page_expired(): void
    {
        $limit = (string) ini_get('post_max_size');
        $bytes = (int) $limit * match (strtolower(substr(trim($limit), -1))) { 'g' => 1024 ** 3, 'm' => 1024 ** 2, 'k' => 1024, default => 1 };
        if ($bytes <= 0) $this->markTestSkipped('post_max_size is unlimited here');

        \Illuminate\Support\Facades\Route::post('/_too-large', fn () => throw new \Illuminate\Session\TokenMismatchException())->middleware('web');

        $this->actingAs(User::factory()->create())->from('/admin/royalties')
            ->call('POST', '/_too-large', [], [], [], ['CONTENT_LENGTH' => $bytes + 1])
            ->assertRedirect('/admin/royalties')->assertSessionHas('error');
    }

    public function test_a_rejected_upload_request_says_what_was_missing(): void
    {
        \Illuminate\Support\Facades\Route::post('/_expired', fn () => throw new \Illuminate\Session\TokenMismatchException())->middleware('web');

        $this->actingAs(User::factory()->create())->postJson('/_expired', ['_token' => 'alt'])
            ->assertStatus(419)
            ->assertJsonPath('diagnose.Token gesendet', 'ja')
            ->assertJsonPath('diagnose.Session-Cookie', 'nein')
            ->assertJsonPath('diagnose.Cookie-Name', config('session.cookie'));

        // The same session cookie twice (another site on the parent domain) shows
        $cookie = config('session.cookie');
        $this->call('POST', '/_expired', [], [], [], ['HTTP_ACCEPT' => 'application/json', 'HTTP_COOKIE' => "{$cookie}=a; other=1; {$cookie}=b"])
            ->assertStatus(419)->assertJsonPath('diagnose.Session-Cookie', '2× (doppelt)');

        // A form gets a page with the same facts instead of the bare "Page Expired"
        $this->post('/_expired', ['_token' => 'alt'])->assertStatus(419)
            ->assertSee('Sitzung abgelaufen (419)')->assertSee('Session-Cookie')->assertSee('Cookie-Domain');
    }

    public function test_a_non_rebeat_upload_shows_an_error(): void
    {
        $path = $this->files[] = tempnam(sys_get_temp_dir(), 'other') . '.csv';
        file_put_contents($path, "a;b;c\n1;2;3\n");

        $this->actingAs(User::factory()->create())->post('/admin/royalties/imports', [
            'file' => new UploadedFile($path, 'liste.csv', null, null, true),
        ])->assertSessionHas('error');
        $this->assertSame(0, RoyaltyImport::count());
    }
}
