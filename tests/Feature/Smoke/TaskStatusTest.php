<?php

namespace Tests\Feature\Smoke;

use App\Models\Project;
use App\Models\Task;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Tasks moved from an is_completed flag to a status field with four values.
 * These cover the paths that flag used to drive.
 */
class TaskStatusTest extends TestCase
{
    use RefreshDatabase;

    protected function actingAsUser()
    {
        return $this->actingAs(User::factory()->create());
    }

    public function test_a_task_defaults_to_open(): void
    {
        $task = Task::create(['title' => 'Ohne Statusangabe']);

        $this->assertSame('open', $task->fresh()->status);
    }

    public function test_task_can_be_created_with_each_status(): void
    {
        foreach (array_keys(Task::STATUSES) as $status) {
            $this->actingAsUser()
                ->post('/admin/tasks', ['title' => 'Aufgabe ' . $status, 'status' => $status])
                ->assertRedirect();

            $this->assertDatabaseHas('tasks', ['title' => 'Aufgabe ' . $status, 'status' => $status]);
        }
    }

    public function test_unknown_status_is_rejected(): void
    {
        $this->actingAsUser()
            ->post('/admin/tasks', ['title' => 'Kaputt', 'status' => 'erfunden'])
            ->assertSessionHasErrors('status');

        $this->assertDatabaseMissing('tasks', ['title' => 'Kaputt']);
    }

    /**
     * The checkbox is a two-way switch between open and completed, no matter
     * which status the task carried before.
     */
    public function test_toggle_switches_between_open_and_completed(): void
    {
        $task = Task::create(['title' => 'Umschalten', 'status' => 'open']);

        $this->actingAsUser()->patch("/admin/tasks/{$task->id}/toggle");
        $this->assertSame('completed', $task->fresh()->status);

        $this->actingAsUser()->patch("/admin/tasks/{$task->id}/toggle");
        $this->assertSame('open', $task->fresh()->status);
    }

    public function test_toggling_a_held_task_completes_it(): void
    {
        $task = Task::create(['title' => 'Pausiert', 'status' => 'on_hold']);

        $this->actingAsUser()->patch("/admin/tasks/{$task->id}/toggle");

        $this->assertSame('completed', $task->fresh()->status);
    }

    public function test_overview_can_filter_by_each_status(): void
    {
        foreach (array_keys(Task::STATUSES) as $status) {
            Task::create(['title' => 'Aufgabe ' . $status, 'status' => $status]);
        }

        foreach (array_keys(Task::STATUSES) as $status) {
            $titles = $this->actingAsUser()
                ->get('/admin/tasks?status=' . $status)
                ->viewData('tasks')
                ->pluck('title')
                ->all();

            $this->assertSame(['Aufgabe ' . $status], $titles, "Filter failed for status: {$status}");
        }
    }

    /**
     * Only "open" counts as overdue - a paused or abandoned task is not late.
     */
    public function test_only_open_tasks_can_be_overdue(): void
    {
        $yesterday = now()->subDay();

        $this->assertTrue(Task::create(['title' => 'a', 'status' => 'open', 'due_date' => $yesterday])->isOverdue());
        $this->assertFalse(Task::create(['title' => 'b', 'status' => 'on_hold', 'due_date' => $yesterday])->isOverdue());
        $this->assertFalse(Task::create(['title' => 'c', 'status' => 'not_implemented', 'due_date' => $yesterday])->isOverdue());
        $this->assertFalse(Task::create(['title' => 'd', 'status' => 'completed', 'due_date' => $yesterday])->isOverdue());
    }

    public function test_task_pages_render_for_every_status(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        foreach (array_keys(Task::STATUSES) as $status) {
            Task::create(['title' => 'Aufgabe ' . $status, 'project_id' => $project->id, 'status' => $status]);
        }

        $this->actingAsUser()->get('/admin/tasks')->assertOk();
        $this->actingAsUser()->get("/admin/projects/{$project->id}")->assertOk();

        foreach (Task::all() as $task) {
            $this->actingAsUser()->get("/admin/tasks/{$task->id}")->assertOk()
                ->assertSee($task->status_label);
            $this->actingAsUser()->get("/admin/tasks/{$task->id}/edit")->assertOk();
        }
    }

    /**
     * "Not implemented" resolves a task, so the project counter has to include
     * it - otherwise a project with abandoned tasks never reaches completion.
     */
    public function test_project_counter_treats_not_implemented_as_resolved(): void
    {
        $project = Project::create(['name' => 'Alpha', 'status' => 'in_progress']);

        Task::create(['title' => 'a', 'project_id' => $project->id, 'status' => 'completed']);
        Task::create(['title' => 'b', 'project_id' => $project->id, 'status' => 'not_implemented']);
        Task::create(['title' => 'c', 'project_id' => $project->id, 'status' => 'open']);

        $this->actingAsUser()->get('/admin/projects')->assertOk()->assertSee('2/3');
    }
}
