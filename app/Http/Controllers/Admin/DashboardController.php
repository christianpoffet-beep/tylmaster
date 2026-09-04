<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\MusicSubmission;
use App\Models\Task;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /** Active project statuses, in the order they should appear. */
    protected const ACTIVE_PROJECT_STATUSES = ['in_progress', 'planned'];

    public function index(Request $request)
    {
        $stats = [
            'contacts' => Contact::count(),
            'contracts' => Contract::where('status', 'active')->count(),
            'projects' => Project::whereIn('status', self::ACTIVE_PROJECT_STATUSES)->count(),
            'open_invoices' => Invoice::where('status', 'open')->count(),
            'open_tasks' => Task::open()->count(),
            'submissions' => MusicSubmission::where('status', 'new')->count(),
        ];

        $recentContacts = Contact::latest()->take(5)->get();

        // In-progress before planned, then by deadline with undated projects last
        $activeProjects = Project::whereIn('status', self::ACTIVE_PROJECT_STATUSES)
            ->orderByRaw("CASE status WHEN 'in_progress' THEN 0 ELSE 1 END")
            ->orderByRaw('CASE WHEN deadline IS NULL THEN 1 ELSE 0 END')
            ->orderBy('deadline')
            ->get();

        $overdueInvoices = Invoice::where('status', 'open')
            ->where('due_date', '<', now())
            ->get();

        // Optional filter on the task list; 0 and non-numeric input mean "all"
        $projectFilter = $request->integer('project') ?: null;

        // Every open task, not just the ones due soon. On hold and not
        // implemented stay out - the dashboard shows what can be worked on.
        $openTasks = Task::with('project')
            ->open()
            ->when($projectFilter, fn ($q) => $q->where('project_id', $projectFilter))
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->get();

        // Only offer projects that actually have open tasks, so no option comes up empty
        $taskProjects = Project::whereHas('tasks', fn ($q) => $q->open())
            ->orderBy('name')
            ->get(['id', 'name']);

        // Upcoming birthdays (next 21 days)
        $today = now()->startOfDay();
        $limit = $today->copy()->addDays(21);
        $upcomingBirthdays = Contact::whereNotNull('birth_date')
            ->whereNull('death_date')
            ->get()
            ->map(function ($contact) use ($today) {
                $birthday = $contact->birth_date->copy()->year($today->year)->startOfDay();
                if ($birthday->lt($today)) {
                    $birthday->addYear();
                }
                $contact->next_birthday = $birthday;
                $contact->turns_age = $birthday->year - $contact->birth_date->year;
                return $contact;
            })
            ->filter(fn ($c) => $c->next_birthday->between($today, $limit))
            ->sortBy('next_birthday')
            ->values();

        return view('admin.dashboard', compact(
            'stats', 'recentContacts', 'activeProjects', 'overdueInvoices',
            'openTasks', 'upcomingBirthdays', 'taskProjects', 'projectFilter'
        ));
    }
}
