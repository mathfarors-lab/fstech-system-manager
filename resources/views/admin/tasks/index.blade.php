@extends('layouts.app')

@section('title', 'Tasks')

@section('content')
<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-4">
    <div>
        <h4 class="fw-bold mb-0">Task Board</h4>
        <small class="text-body-secondary">Drag cards between columns to update status</small>
    </div>
    <div class="d-flex flex-wrap gap-2 align-items-center">
        <form method="GET" action="{{ route('tasks.index') }}" class="d-flex gap-2">
            <select name="system" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">All systems</option>
                @foreach($systems as $s)
                    <option value="{{ $s->id }}" {{ ($filters['system'] ?? null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
            <select name="assignee" class="form-select form-select-sm" style="width:auto" onchange="this.form.submit()">
                <option value="">All assignees</option>
                @foreach($users as $u)
                    <option value="{{ $u->id }}" {{ ($filters['assignee'] ?? null) == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>
                @endforeach
            </select>
        </form>
        @if(auth()->user()->canWrite())
            <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal"><i class="bi bi-plus-lg me-1"></i>New Task</button>
        @endif
    </div>
</div>

<div class="fst-kanban" id="kanban">
    @foreach(\App\Models\Task::STATUSES as $statusKey => $statusLabel)
        <div class="fst-kanban-col" data-status="{{ $statusKey }}">
            <div class="col-head">
                <div class="d-flex align-items-center gap-2">
                    <span class="fst-col-dot" data-status="{{ $statusKey }}"></span>
                    <span class="fw-semibold small">{{ $statusLabel }}</span>
                </div>
                <span class="fst-col-count badge bg-body-tertiary text-body border">{{ ($tasks[$statusKey] ?? collect())->count() }}</span>
            </div>
            <div class="fst-kanban-body">
                @forelse($tasks[$statusKey] ?? collect() as $task)
                    <div class="fst-task-card" data-priority="{{ $task->priority }}" data-status="{{ $task->status }}"
                         data-status-url="{{ auth()->user()->canWrite() ? route('tasks.status', $task) : '' }}">
                        <div class="d-flex align-items-start justify-content-between gap-2 mb-2">
                            <a href="{{ route('tasks.show', $task) }}" class="fw-semibold small fst-task-title link-plain">{{ $task->title }}</a>
                            <x-status-pill :status="$task->priority" />
                        </div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="fst-task-system"><i class="bi bi-hdd-stack"></i>{{ $task->system->name }}</span>
                            @if($task->due_date && $task->status !== 'done')
                                <span class="fst-task-due ms-auto {{ $task->due_date->isPast() ? 'overdue' : '' }}">
                                    <i class="bi bi-calendar3 me-1"></i>{{ $task->due_date->format('M j') }}
                                    @if($task->due_date->isPast())<i class="bi bi-exclamation-triangle-fill ms-1"></i>@endif
                                </span>
                            @endif
                        </div>
                        @if($task->assignee)
                            <div class="mt-2 pt-2 border-top d-flex align-items-center gap-1">
                                <span class="avatar avatar-sm" style="background:linear-gradient(135deg,#6366f1,#a855f7)">{{ strtoupper(substr($task->assignee->name, 0, 1)) }}</span>
                                <small class="text-body-secondary">{{ $task->assignee->name }}</small>
                                @if(auth()->user()->canWrite())
                                    <div class="ms-auto d-flex gap-1">
                                        <button class="btn btn-sm btn-light border py-0 px-1" data-bs-toggle="modal" data-bs-target="#editTaskModal{{ $task->id }}"><i class="bi bi-pencil small"></i></button>
                                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="fstConfirm(event, 'Delete task \'{{ $task->title }}\'?')">@csrf @method('DELETE')
                                            <button class="btn btn-sm btn-light border py-0 px-1"><i class="bi bi-trash small text-danger"></i></button>
                                        </form>
                                    </div>
                                @endif
                            </div>
                        @endif
                        @include('admin.tasks._notes')

                        @if(auth()->user()->canWrite() && $task->nextStatusAction())
                            @php $next = $task->nextStatusAction(); $ready = $task->allNotesDone(); @endphp
                            <form method="POST" action="{{ route('tasks.status', $task) }}" class="mt-2 pt-2 border-top">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $next['status'] }}">
                                <button class="btn btn-sm w-100 {{ $ready ? 'btn-fst-primary' : 'btn-light border text-body-secondary' }}" title="{{ $next['label'] }}">
                                    <i class="bi {{ $next['icon'] }} me-1"></i>{{ $next['label'] }}
                                    @if($ready && $task->status === 'in_progress')<i class="bi bi-check2-circle ms-1"></i>@endif
                                </button>
                            </form>
                        @endif
                    </div>
                @empty
                    <div class="fst-kanban-empty">
                        <i class="bi bi-inbox"></i>
                        <div class="small mt-1">No tasks</div>
                    </div>
                @endforelse
            </div>
        </div>
    @endforeach
</div>

@if(auth()->user()->canWrite())
    @php $allSystems = $systems; @endphp
    <div class="modal fade" id="taskModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header"><h5 class="modal-title fw-bold">New Task</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                <form method="POST" action="{{ route('tasks.store') }}">
                    @csrf
                    <div class="modal-body row g-3">
                        <div class="col-md-6"><label class="form-label small fw-semibold">System <span class="text-danger">*</span></label>
                            <select name="system_id" class="form-select" required>
                                <option value="">— select —</option>
                                @foreach($allSystems as $s)<option value="{{ $s->id }}">{{ $s->name }}</option>@endforeach
                            </select></div>
                        <div class="col-md-6"><label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" required></div>
                        <div class="col-12"><label class="form-label small fw-semibold">Description</label>
                            <textarea name="description" rows="3" class="form-control"></textarea></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Status</label>
                            <select name="status" class="form-select">@foreach(\App\Models\Task::STATUSES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Priority</label>
                            <select name="priority" class="form-select">@foreach(\App\Models\Task::PRIORITIES as $k => $l)<option value="{{ $k }}" {{ $k === 'medium' ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Assignee</label>
                            <select name="assignee_id" class="form-select"><option value="">— unassigned —</option>
                                @foreach($users as $u)<option value="{{ $u->id }}">{{ $u->name }}</option>@endforeach</select></div>
                        <div class="col-md-3"><label class="form-label small fw-semibold">Due date</label>
                            <input type="date" name="due_date" class="form-control"></div>
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button class="btn btn-fst-primary">Create Task</button></div>
                </form>
            </div>
        </div>
    </div>

    @foreach(\App\Models\Task::STATUSES as $statusKey => $statusLabel)
        @foreach($tasks[$statusKey] ?? collect() as $task)
            <div class="modal fade" id="editTaskModal{{ $task->id }}" tabindex="-1">
                <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header"><h5 class="modal-title fw-bold">Edit Task</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
                    <form method="POST" action="{{ route('tasks.update', $task) }}" id="editTaskForm{{ $task->id }}">
                        @csrf @method('PUT')
                        <div class="modal-body row g-3">
                            <div class="col-md-6"><label class="form-label small fw-semibold">System</label>
                                <select name="system_id" class="form-select">
                                    @foreach($allSystems as $s)<option value="{{ $s->id }}" {{ $task->system_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
                                </select></div>
                            <div class="col-md-6"><label class="form-label small fw-semibold">Title</label>
                                <input type="text" name="title" class="form-control" value="{{ old('title', $task->title) }}" required></div>
                            <div class="col-12"><label class="form-label small fw-semibold">Description</label>
                                <textarea name="description" rows="3" class="form-control">{{ old('description', $task->description) }}</textarea></div>
                            <div class="col-md-3"><label class="form-label small fw-semibold">Status</label>
                                <select name="status" class="form-select">@foreach(\App\Models\Task::STATUSES as $k => $l)<option value="{{ $k }}" {{ $task->status === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label small fw-semibold">Priority</label>
                                <select name="priority" class="form-select">@foreach(\App\Models\Task::PRIORITIES as $k => $l)<option value="{{ $k }}" {{ $task->priority === $k ? 'selected' : '' }}>{{ $l }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label small fw-semibold">Assignee</label>
                                <select name="assignee_id" class="form-select"><option value="">— unassigned —</option>
                                    @foreach($users as $u)<option value="{{ $u->id }}" {{ $task->assignee_id == $u->id ? 'selected' : '' }}>{{ $u->name }}</option>@endforeach</select></div>
                            <div class="col-md-3"><label class="form-label small fw-semibold">Due date</label>
                                <input type="date" name="due_date" class="form-control" value="{{ $task->due_date?->format('Y-m-d') }}"></div>
                        </div>
                    </form>
                    <div class="modal-body border-top">
                        @include('admin.tasks._notes')
                    </div>
                    <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-fst-primary" form="editTaskForm{{ $task->id }}">Save</button></div>
                </div>
                </div>
            </div>
        @endforeach
    @endforeach
@endif
@endsection
