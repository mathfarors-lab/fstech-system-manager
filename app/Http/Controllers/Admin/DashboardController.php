<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Build;
use App\Models\OperationLog;
use App\Models\System;
use App\Models\Task;
use App\Models\Version;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $myTasks = Task::with(['system', 'notes'])
            ->where('assignee_id', $request->user()->id)
            ->whereIn('status', ['todo', 'in_progress', 'review'])
            ->orderByRaw("CASE priority WHEN 'urgent' THEN 0 WHEN 'high' THEN 1 WHEN 'medium' THEN 2 ELSE 3 END")
            ->orderBy('due_date')
            ->limit(6)
            ->get();

        $activity = collect()
            ->merge(
                Build::with(['system', 'builtBy'])->latest('created_at')->limit(8)->get()
                    ->map(fn ($b) => [
                        'icon' => 'rocket-takeoff',
                        'color' => match ($b->status) {
                            'deployed' => 'success',
                            'failed' => 'danger',
                            'building' => 'warning',
                            default => 'secondary',
                        },
                        'text' => "Build {$b->version_label} (".($b->statusLabel()).') for',
                        'subject' => $b->system?->name ?? '—',
                        'user' => $b->builtBy?->name,
                        'at' => $b->created_at,
                    ])
            )
            ->merge(
                Task::with(['system', 'createdBy'])->latest('created_at')->limit(6)->get()
                    ->map(fn ($t) => [
                        'icon' => 'kanban',
                        'color' => 'primary',
                        'text' => 'Task "'.str($t->title)->limit(40).'" created for',
                        'subject' => $t->system?->name ?? '—',
                        'user' => $t->createdBy?->name,
                        'at' => $t->created_at,
                    ])
            )
            ->merge(
                OperationLog::with(['system', 'performedBy'])->latest('performed_at')->limit(6)->get()
                    ->map(fn ($l) => [
                        'icon' => 'clock-history',
                        'color' => 'info',
                        'text' => 'Ops log "'.str($l->title)->limit(40).'" for',
                        'subject' => $l->system?->name ?? '—',
                        'user' => $l->performedBy?->name,
                        'at' => $l->performed_at,
                    ])
            )
            ->sortByDesc('at')
            ->take(12)
            ->values();

        $stats = [
            'systems' => System::count(),
            'activeSystems' => System::where('status', 'active')->count(),
            'building' => Build::whereIn('status', ['planned', 'building'])->count(),
            'openTasks' => Task::where('status', '!=', 'done')->count(),
            'myOpenTasks' => Task::where('assignee_id', $request->user()->id)->where('status', '!=', 'done')->count(),
            'versions' => Version::count(),
        ];

        $statusChart = System::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        $stackChart = System::selectRaw('stack, count(*) as total')
            ->groupBy('stack')
            ->pluck('total', 'stack');

        $taskChart = Task::selectRaw('status, count(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status');

        return view('dashboard', compact(
            'myTasks', 'activity', 'stats', 'statusChart', 'stackChart', 'taskChart'
        ));
    }
}
