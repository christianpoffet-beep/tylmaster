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

        Task::create(['title' => 'Alpha-Aufgabe', 'project_id' => $alpha->id, 'status' => 'open']);
        Task::create(['title' => 'Beta-Aufgabe', 'project_id' => $beta->id, 'status' => 'open']);
        Task::create(['title' => 'Ohne Projekt', 'status' => 'open']);

        // Unfiltered: everything shows
        $all = $this->actingAsUser()->get('/admin');
        $this->assertCount(3, $all->viewData('openTasks'));

        // Filtered: only that project's tasks
        $filtered = $this->actingAsUser()->get('/admin?project=' . $alpha->id);
        $titles = $filtered->viewData('openTasks')->pluck('title')->all();

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

        Task::create(['title' => 'offen', 'project_id' => $withTask->id, 'status' => 'open']);
        Task::create(['title' => 'erledigt', 'project_id' => $done->id, 'status' => 'completed']);

        $names = $this->actingAsUser()->get('/admin')->viewData('taskProjects')->pluck('name')->all();

        $this->assertSame(['Mit Aufgabe'], $names);
    }

    public function test_invalid_project_filter_falls_back_to_all_tasks(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);
        Task::create(['title' => 'Alpha-Aufgabe', 'project_id' => $project->id, 'status' => 'open']);
        Task::create(['title' => 'Ohne Projekt', 'status' => 'open']);

        $response = $this->actingAsUser()->get('/admin?project=keine-zahl');

        $response->assertOk();
        $this->assertNull($response->viewData('projectFilter'));
        $this->assertCount(2, $response->viewData('openTasks'));
    }

    /**
     * The dashboard lists what can be worked on: on hold and not implemented
     * are both kept out, alongside completed.
     */
    public function test_dashboard_lists_only_open_tasks(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        foreach (['open', 'on_hold', 'not_implemented', 'completed'] as $status) {
            Task::create(['title' => 'Aufgabe ' . $status, 'project_id' => $project->id, 'status' => $status]);
        }

        $response = $this->actingAsUser()->get('/admin');
        $titles = $response->viewData('openTasks')->pluck('title')->all();

        $this->assertSame(['Aufgabe open'], $titles);
        $this->assertSame(1, $response->viewData('stats')['open_tasks']);
    }

    /**
     * The old list only reached seven days ahead and stopped at ten entries.
     */
    public function test_dashboard_lists_every_open_task_regardless_of_due_date(): void
    {
        for ($i = 0; $i < 14; $i++) {
            Task::create([
                'title' => 'Aufgabe ' . $i,
                'status' => 'open',
                'due_date' => now()->addDays($i * 30),
            ]);
        }

        $tasks = $this->actingAsUser()->get('/admin')->viewData('openTasks');

        $this->assertCount(14, $tasks);
    }
}
