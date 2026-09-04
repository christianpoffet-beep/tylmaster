<?php

namespace Tests\Feature\Smoke;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsUser()
    {
        return $this->actingAs(User::factory()->create());
    }

    /**
     * Projects are grouped by status first (running before planned) and only
     * then by deadline, with undated projects at the end of their group.
     */
    public function test_projects_are_sorted_by_status_then_deadline(): void
    {
        Project::create(['name' => 'Geplant frueh', 'status' => 'planned', 'deadline' => '2026-01-01']);
        Project::create(['name' => 'Laufend spaet', 'status' => 'in_progress', 'deadline' => '2026-12-01']);
        Project::create(['name' => 'Laufend ohne Datum', 'status' => 'in_progress', 'deadline' => null]);
        Project::create(['name' => 'Laufend frueh', 'status' => 'in_progress', 'deadline' => '2026-03-01']);
        Project::create(['name' => 'Abgeschlossen', 'status' => 'completed', 'deadline' => '2026-02-01']);

        $response = $this->actingAsUser()->get('/admin');
        $response->assertOk();

        $names = $response->viewData('activeProjects')->pluck('name')->all();

        $this->assertSame([
            'Laufend frueh',
            'Laufend spaet',
            'Laufend ohne Datum',
            'Geplant frueh',
        ], $names);
    }

    public function test_tasks_can_be_filtered_by_project(): void
    {
        $alpha = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);
        $beta = Project::create(['name' => 'Beta', 'status' => 'in_progress']);

        Task::create(['title' => 'Alpha-Aufgabe', 'project_id' => $alpha->id, 'is_completed' => false]);
        Task::create(['title' => 'Beta-Aufgabe', 'project_id' => $beta->id, 'is_completed' => false]);
        Task::create(['title' => 'Ohne Projekt', 'is_completed' => false]);

        // Unfiltered: everything shows
        $all = $this->actingAsUser()->get('/admin');
        $this->assertCount(3, $all->viewData('upcomingTasks'));

        // Filtered: only that project's tasks
        $filtered = $this->actingAsUser()->get('/admin?project=' . $alpha->id);
        $titles = $filtered->viewData('upcomingTasks')->pluck('title')->all();

        $this->assertSame(['Alpha-Aufgabe'], $titles);
        $this->assertSame($alpha->id, $filtered->viewData('projectFilter'));
    }

    /**
     * The dropdown must not offer projects that would yield an empty list.
     */
    public function test_filter_offers_only_projects_with_open_tasks(): void
    {
        $withTask = Project::create(['name' => 'Mit Aufgabe', 'status' => 'in_progress']);
        $done = Project::create(['name' => 'Nur erledigte', 'status' => 'in_progress']);
        Project::create(['name' => 'Ganz ohne', 'status' => 'in_progress']);

        Task::create(['title' => 'offen', 'project_id' => $withTask->id, 'is_completed' => false]);
        Task::create(['title' => 'erledigt', 'project_id' => $done->id, 'is_completed' => true]);

        $names = $this->actingAsUser()->get('/admin')->viewData('taskProjects')->pluck('name')->all();

        $this->assertSame(['Mit Aufgabe'], $names);
    }

    public function test_invalid_project_filter_falls_back_to_all_tasks(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);
        Task::create(['title' => 'Alpha-Aufgabe', 'project_id' => $project->id, 'is_completed' => false]);
        Task::create(['title' => 'Ohne Projekt', 'is_completed' => false]);

        $response = $this->actingAsUser()->get('/admin?project=keine-zahl');

        $response->assertOk();
        $this->assertNull($response->viewData('projectFilter'));
        $this->assertCount(2, $response->viewData('upcomingTasks'));
    }
}
