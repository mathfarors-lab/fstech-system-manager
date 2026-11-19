@extends('layouts.app')

@section('title', 'Ops Logs')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Operation Logs</h4>
        <small class="text-body-secondary">Backups, maintenance and issues across all systems</small>
    </div>
    <form method="GET" action="{{ route('logs.index') }}" class="d-flex gap-2">
        <select name="system" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <option value="">All systems</option>
            @foreach($systems as $s)
                <option value="{{ $s->id }}" {{ ($filters['system'] ?? null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="type" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
            <option value="">All types</option>
            @foreach(\App\Models\OperationLog::TYPES as $k => $l)
                <option value="{{ $k }}" {{ ($filters['type'] ?? null) === $k ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
    </form>
</div>

<div class="fst-card p-4">
    <div class="fst-timeline">
        @forelse($logs as $log)
            <div class="fst-timeline-item">
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <a href="{{ route('systems.show', [$log->system, 'tab' => 'logs']) }}" class="link-plain small fw-semibold">{{ $log->system->name }}</a>
                    <x-status-pill :status="$log->type" />
                    <strong class="small">{{ $log->title }}</strong>
                    <small class="text-body-secondary">{{ $log->performed_at->format('M j, Y g:ia') }}</small>
                    <small class="ms-auto text-body-secondary"><i class="bi bi-person me-1"></i>{{ $log->performedBy?->name ?? 'System' }}</small>
                </div>
                @if($log->details)<div class="small text-body-secondary mt-1">{{ $log->details }}</div>@endif
            </div>
        @empty
            <p class="text-body-secondary mb-0">No operation logs found</p>
        @endforelse
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-body-secondary">Page {{ $logs->currentPage() }} of {{ $logs->lastPage() }}</small>
    {{ $logs->appends(request()->query())->links() }}
</div>
@endsection
