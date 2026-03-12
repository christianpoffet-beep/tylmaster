<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Contract;
use App\Models\Invoice;
use App\Models\Project;
use App\Models\MusicSubmission;
use App\Models\Task;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'contacts' => Contact::count(),
            'contracts' => Contract::where('status', 'active')->count(),
            'projects' => Project::whereIn('status', ['planned', 'in_progress'])->count(),
            'open_invoices' => Invoice::where('status', 'open')->count(),
            'open_tasks' => Task::where('is_completed', false)->count(),
            'submissions' => MusicSubmission::where('status', 'new')->count(),
        ];

        $recentContacts = Contact::latest()->take(5)->get();
        $activeProjects = Project::whereIn('status', ['planned', 'in_progress'])
            ->orderBy('deadline')
            ->get();
        $overdueInvoices = Invoice::where('status', 'open')
            ->where('due_date', '<', now())
            ->get();
        $upcomingTasks = Task::with('project')->where('is_completed', false)
            ->where(function ($q) {
                $q->where('due_date', '<=', now()->addDays(7))
                  ->orWhereNull('due_date');
            })
            ->orderByRaw('CASE WHEN due_date IS NULL THEN 1 ELSE 0 END, due_date ASC')
            ->take(10)
            ->get();

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

        return view('admin.dashboard', compact('stats', 'recentContacts', 'activeProjects', 'overdueInvoices', 'upcomingTasks', 'upcomingBirthdays'));
    }
}
