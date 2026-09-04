<?php

namespace Tests\Feature\Smoke;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The deploy endpoint runs migrations on the live database, so who may reach it
 * matters more than what it prints. It replaced a set of scripts guarded only by
 * a hardcoded token in a public repository.
 */
class DeployRefreshTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_reach_the_deploy_endpoint(): void
    {
        $this->post('/admin/deploy-refresh')->assertRedirect('/login');
    }

    public function test_non_admin_users_are_refused(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'user']))
            ->post('/admin/deploy-refresh')
            ->assertForbidden();
    }

    public function test_admin_can_run_it_and_gets_the_command_output(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'admin']))
            ->post('/admin/deploy-refresh')
            ->assertRedirect()
            ->assertSessionHas('deployOutput');

        $output = session('deployOutput');

        $this->assertArrayHasKey('migrate', $output);
        $this->assertArrayHasKey('config:clear', $output);
    }

    /**
     * The old token-guarded route must stay gone.
     */
    public function test_the_old_token_route_no_longer_exists(): void
    {
        $this->get('/deploy-refresh/tyl-deploy-2024x')->assertNotFound();
    }
}
