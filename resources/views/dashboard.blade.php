@extends('layouts.app')

@section('title', 'Dashboard')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Dashboard</h4>
        <small class="text-body-secondary">Welcome back, {{ auth()->user()->name }} 👋</small>
    </div>
    @if(auth()->user()->canWrite())
        <a href="{{ route('systems.create') }}" class="btn btn-fst-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New System</a>
    @endif
</div>

<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3"><x-stat-card icon="bi-hdd-stack-fill" :value="$stats['systems']" label="Total Systems" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat-card icon="bi-activity" :value="$stats['activeSystems']" label="Active Systems" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat-card icon="bi-rocket-takeoff-fill" :value="$stats['building']" label="Builds in Progress" /></div>
    <div class="col-sm-6 col-xl-3"><x-stat-card icon="bi-kanban-fill" :value="$stats['openTasks']" label="Open Tasks" /></div>
</div>

<div class="row g-3">
    {{-- Charts --}}
    <div class="col-lg-5">
        <x-card class="h-100" title="Systems by Status" :subtitle="$stats['versions'] . ' versions tracked across all systems'">
            <div class="fst-chart"><canvas id="statusChart"></canvas></div>
        </x-card>
    </div>
    <div class="col-lg-3">
        <x-card class="h-100" title="Stacks">
            <div class="fst-chart"><canvas id="stackChart"></canvas></div>
        </x-card>
    </div>
    <div class="col-lg-4">
        <x-card class="h-100" title="Tasks" :subtitle="$stats['myOpenTasks'] . ' assigned to you'">
            <div class="fst-chart"><canvas id="taskChart"></canvas></div>
        </x-card>
    </div>

    {{-- My tasks --}}
    <div class="col-lg-7">
        <x-card title="My Open Tasks" :subtitle="$stats['myOpenTasks'].' tasks assigned to you'">
            <x-slot:actions>
                <a class="btn btn-sm btn-light border" href="{{ route('tasks.index') }}">View board</a>
            </x-slot:actions>
            @forelse($myTasks as $task)
                <div class="d-flex align-items-center gap-3 py-2 {{ !$loop->first ? 'border-top' : '' }}">
                    <span class="avatar avatar-sm" style="background:linear-gradient(135deg,#6366f1,#a855f7)">{{ strtoupper(substr($task->system->name, 0, 1)) }}</span>
                    <div class="flex-grow-1 min-width-0">
                        <a class="link-plain fw-semibold small d-block text-truncate" href="{{ route('systems.show', [$task->system, 'tab' => 'tasks']) }}">{{ $task->title }}</a>
                        <small class="text-body-secondary">{{ $task->system->name }}
                            @if($task->notes->count())
                                <i class="bi bi-chat-left-text ms-1"></i>{{ $task->notes->where('is_done', true)->count() }}/{{ $task->notes->count() }}
                            @endif
                            @if($task->due_date) · due {{ $task->due_date->diffForHumans() }} @endif</small>
                    </div>
                    <x-status-pill :status="$task->priority" />
                    <x-status-pill :status="$task->status" />
                </div>
            @empty
                <div class="text-center py-4 text-body-secondary">
                    <i class="bi bi-check2-circle" style="font-size:2rem"></i>
                    <p class="small mb-0 mt-2">No open tasks assigned to you</p>
                </div>
            @endforelse
        </x-card>
    </div>

    {{-- Activity feed --}}
    <div class="col-lg-5">
        <x-card title="Recent Activity">
            <div class="fst-timeline">
                @foreach($activity as $item)
                    <div class="fst-timeline-item">
                        <div class="d-flex align-items-center gap-2 small">
                            <i class="bi {{ $item['icon'] }} text-{{ $item['color'] }}"></i>
                            <span>{{ $item['text'] }} <strong>{{ $item['subject'] }}</strong></span>
                        </div>
                        <small class="text-body-secondary">{{ $item['user'] ?? 'System' }} · {{ $item['at']->diffForHumans() }}</small>
                    </div>
                @endforeach
                @if($activity->isEmpty())
                    <div class="text-center py-4 text-body-secondary"><p class="small mb-0">No activity yet</p></div>
                @endif
            </div>
        </x-card>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const gridColor = getComputedStyle(document.documentElement).getPropertyValue('--bs-border-color').trim();

    function makeChart(id, config) {
        const el = document.getElementById(id);
        if (!el) return;
        new Chart(el, config);
    }

    const palette = ['#6366f1', '#a855f7', '#06b6d4', '#22c55e', '#f59e0b', '#ef4444', '#8b5cf6'];

    makeChart('statusChart', {
        type: 'doughnut',
        data: {
            labels: @json($statusChart->map(fn ($v, $k) => \App\Models\System::STATUSES[$k] ?? $k)->values()),
            datasets: [{ data: @json($statusChart->values()), backgroundColor: palette, borderWidth: 0 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom' } }, cutout: '68%' }
    });

    makeChart('stackChart', {
        type: 'polarArea',
        data: {
            labels: @json($stackChart->map(fn ($v, $k) => \App\Models\System::STACKS[$k] ?? $k)->values()),
            datasets: [{ data: @json($stackChart->values()), backgroundColor: ['rgba(99,102,241,.6)','rgba(168,85,247,.6)','rgba(6,182,212,.6)','rgba(34,197,94,.6)','rgba(245,158,11,.6)','rgba(239,68,68,.6)','rgba(139,92,246,.6)'], borderWidth: 0 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { boxWidth: 10 } } }, scales: { r: { grid: { color: gridColor }, ticks: { display: false } } } }
    });

    makeChart('taskChart', {
        type: 'bar',
        data: {
            labels: @json($taskChart->map(fn ($v, $k) => \App\Models\Task::STATUSES[$k] ?? $k)->values()),
            datasets: [{ data: @json($taskChart->values()), backgroundColor: 'rgba(99,102,241,.75)', borderRadius: 8, maxBarThickness: 40 }]
        },
        options: { maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { x: { grid: { display: false } }, y: { beginAtZero: true, ticks: { precision: 0 }, grid: { color: gridColor } } } }
    });
</script>
@endpush
