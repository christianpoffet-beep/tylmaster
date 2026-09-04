<?php

namespace Tests\Feature\Smoke;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A task reaches a project two ways: as its main project (project_id) or
 * through the taskable link. The project page used to show only the first,
 * so tasks attached through the search field went missing there.
 */
class ProjectTaskLinkTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsUser()
    {
        return $this->actingAs(User::factory()->create());
    }

    public function test_project_shows_tasks_attached_either_way(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        $main = Task::create(['title' => 'Ueber Hauptprojekt', 'project_id' => $project->id, 'status' => 'open']);
        $linked = Task::create(['title' => 'Ueber Verknuepfung', 'status' => 'open']);
        $linked->projects()->attach($project->id);

        $this->actingAsUser()->get("/admin/projects/{$project->id}")
            ->assertOk()
            ->assertSee($main->title)
            ->assertSee($linked->title);
    }

    /**
     * A task attached both ways must appear once, not twice.
     */
    public function test_a_task_attached_both_ways_is_listed_once(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        $task = Task::create(['title' => 'Doppelt verbunden', 'project_id' => $project->id, 'status' => 'open']);
        $task->projects()->attach($project->id);

        $this->assertCount(1, $project->fresh()->all_tasks);
    }

    public function test_project_counter_includes_linked_tasks(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        Task::create(['title' => 'a', 'project_id' => $project->id, 'status' => 'completed']);
        $linked = Task::create(['title' => 'b', 'status' => 'open']);
        $linked->projects()->attach($project->id);

        $this->actingAsUser()->get('/admin/projects')->assertOk()->assertSee('1/2');
    }

    /**
     * Open tasks come first, then on hold, then the closed ones.
     */
    public function test_project_tasks_are_ordered_by_status_then_due_date(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        Task::create(['title' => 'erledigt', 'project_id' => $project->id, 'status' => 'completed', 'due_date' => '2026-01-01']);
        Task::create(['title' => 'offen ohne Datum', 'project_id' => $project->id, 'status' => 'open']);
        Task::create(['title' => 'pausiert', 'project_id' => $project->id, 'status' => 'on_hold', 'due_date' => '2026-02-01']);
        Task::create(['title' => 'offen frueh', 'project_id' => $project->id, 'status' => 'open', 'due_date' => '2026-03-01']);

        $titles = $project->fresh()->all_tasks->pluck('title')->all();

        $this->assertSame(['offen frueh', 'offen ohne Datum', 'pausiert', 'erledigt'], $titles);
    }
}
