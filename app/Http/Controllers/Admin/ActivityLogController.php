<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ActivityLogController extends Controller
{
    public function index(Request $request)
    {
        $query = $this->applyFilters(ActivityLog::query()->latest(), $request);

        $logs = $query->paginate(50)->withQueryString();

        $modelTypes = ActivityLog::select('model_type')
            ->distinct()
            ->pluck('model_type');

        return view('admin.activity-logs.index', compact('logs', 'modelTypes'));
    }

    public function export(Request $request): StreamedResponse
    {
        $query = $this->applyFilters(ActivityLog::query()->latest(), $request);

        $filename = 'aktivitaetslog_' . now()->format('Y-m-d_His') . '.csv';

        return response()->streamDownload(function () use ($query) {
            $handle = fopen('php://output', 'w');

            // BOM for Excel UTF-8 compatibility
            fwrite($handle, "\xEF\xBB\xBF");

            // Header
            fputcsv($handle, ['Datum', 'Benutzer', 'Aktion', 'Bereich', 'Feld', 'Alter Wert', 'Neuer Wert'], ';');

            // Data in chunks
            $query->chunk(500, function ($logs) use ($handle) {
                foreach ($logs as $log) {
                    fputcsv($handle, [
                        $log->created_at->format('d.m.Y H:i'),
                        $log->user_name,
                        $log->action_label,
                        $log->model_type_label,
                        $log->field ?? '',
                        $log->old_value ?? 'null',
                        $log->new_value ?? 'null',
                    ], ';');
                }
            });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function applyFilters($query, Request $request)
    {
        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('model_label', 'like', "%{$search}%")
                  ->orWhere('field', 'like', "%{$search}%")
                  ->orWhere('old_value', 'like', "%{$search}%")
                  ->orWhere('new_value', 'like', "%{$search}%")
                  ->orWhere('user_name', 'like', "%{$search}%");
            });
        }

        if ($action = $request->input('action')) {
            $query->where('action', $action);
        }

        if ($modelType = $request->input('model_type')) {
            $query->where('model_type', $modelType);
        }

        if ($dateFrom = $request->input('date_from')) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo = $request->input('date_to')) {
            $query->whereDate('created_at', '<=', $dateTo);
        }

        return $query;
    }
}
