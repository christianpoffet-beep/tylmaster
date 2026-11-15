<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use App\Services\DeployFinisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * There is no SSH on the hosting. After Plesk has deployed, the first request
 * runs what the "Migrationen & Cache" button does - once per deploy, and
 * never again on every request when it failed.
 */
class DeployAutoFinishTest extends TestCase
{
    use RefreshDatabase;

    private string $marker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->marker = sys_get_temp_dir() . '/tyl-deploy-test-' . uniqid() . '.json';
        config([
            'deploy.auto_finish' => true,
            'deploy.marker' => $this->marker,
            'deploy.settle_seconds' => 0,
        ]);
    }

    protected function tearDown(): void
    {
        @unlink($this->marker);
        @unlink($this->marker . '.lock');

        parent::tearDown();
    }

    private function expectCommandsRun(int $times, ?callable $result = null): void
    {
        Artisan::shouldReceive('call')
            ->times($times * count(DeployFinisher::COMMANDS))
            ->andReturnUsing($result ?? fn () => 0);
        Artisan::shouldReceive('output')->andReturn('');
    }

    private function marker(): array
    {
        return json_decode((string) file_get_contents($this->marker), true);
    }

    public function test_the_first_request_after_a_deploy_finishes_it_once(): void
    {
        $this->expectCommandsRun(1);

        $this->get('/login')->assertOk();
        $this->get('/login')->assertOk();
        $this->get('/login')->assertOk();

        $this->assertTrue($this->marker()['ok']);
        $this->assertSame('auto', $this->marker()['trigger']);
    }

    public function test_a_failed_run_is_not_repeated_on_every_request(): void
    {
        $this->expectCommandsRun(1, fn ($command) => $command === 'migrate' ? throw new \RuntimeException('Tabelle existiert schon') : 0);

        $this->get('/login')->assertOk();
        $this->get('/login')->assertOk();

        $this->assertFalse($this->marker()['ok']);
        $this->assertStringContainsString('Tabelle existiert schon', $this->marker()['output']['migrate']);
    }

    /** After a failure the button is the way to retry, and a good run clears the failed state. */
    public function test_the_button_records_its_run_so_the_middleware_stays_quiet(): void
    {
        $this->expectCommandsRun(1);

        // The button request itself would otherwise already be finished by the middleware.
        config(['deploy.auto_finish' => false]);
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/deploy-refresh')
            ->assertRedirect();

        config(['deploy.auto_finish' => true]);
        $this->get('/login');

        $this->assertTrue($this->marker()['ok']);
        $this->assertSame('button', $this->marker()['trigger']);
    }

    public function test_a_deploy_that_is_still_copying_files_is_left_alone(): void
    {
        config(['deploy.settle_seconds' => 10 * 365 * 24 * 3600]);
        $this->expectCommandsRun(0);

        $this->get('/login')->assertOk();

        $this->assertFileDoesNotExist($this->marker);
    }

    public function test_it_stays_off_unless_switched_on(): void
    {
        config(['deploy.auto_finish' => false]);
        $this->expectCommandsRun(0);

        $this->get('/login')->assertOk();

        $this->assertFileDoesNotExist($this->marker);
    }

    /** A crash in there must never take a page down with it. */
    public function test_a_broken_marker_location_does_not_break_the_page(): void
    {
        config(['deploy.marker' => '/this/directory/does/not/exist/deploy.json']);
        $this->expectCommandsRun(0);

        $this->get('/login')->assertOk();
    }

    public function test_the_system_page_shows_the_last_run(): void
    {
        $this->expectCommandsRun(1, fn ($command) => $command === 'migrate' ? throw new \RuntimeException('Tabelle existiert schon') : 0);
        app(DeployFinisher::class)->run('auto');

        $html = $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->get(route('admin.settings.system'))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression('/Automatisch nach Deploy:<\/span>\s*<span[^>]*>aktiv</', $html);
        $this->assertStringContainsString('automatisch nach Deploy', $html);
        $this->assertStringContainsString('fehlgeschlagen', $html);
        $this->assertStringContainsString('Tabelle existiert schon', $html);
    }
}
