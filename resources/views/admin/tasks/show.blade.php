@extends('layouts.app')

@section('title', $task->title)

@section('content')
<nav aria-label="breadcrumb">
    <ol class="breadcrumb small mb-3">
        <li class="breadcrumb-item"><a href="{{ route('tasks.index') }}" class="link-plain">Task Board</a></li>
        <li class="breadcrumb-item"><a href="{{ route('tasks.index', ['system' => $task->system_id]) }}" class="link-plain">{{ $task->system->name }}</a></li>
        <li class="breadcrumb-item active text-body-secondary" aria-current="page">{{ $task->title }}</li>
    </ol>
</nav>

<div class="fst-card p-4 mb-3">
    <div class="d-flex flex-wrap align-items-start gap-3">
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="fw-bold mb-0 {{ $task->status === 'done' ? 'text-decoration-line-through text-body-secondary' : '' }}">{{ $task->title }}</h4>
                <x-status-pill :status="$task->status" />
                <x-status-pill :status="$task->priority" />
            </div>
            <div class="small text-body-secondary mt-1">
                <i class="bi bi-hdd-stack me-1"></i>
                <a href="{{ route('systems.show', [$task->system, 'tab' => 'tasks']) }}" class="link-plain">{{ $task->system->name }}</a>
                @if($task->due_date && $task->status !== 'done')
                    <i class="bi bi-calendar3 ms-3 me-1 {{ $task->due_date->isPast() ? 'text-danger' : '' }}"></i>
                    <span class="{{ $task->due_date->isPast() ? 'text-danger fw-semibold' : '' }}">Due {{ $task->due_date->format('M j, Y') }}</span>
                @endif
            </div>
        </div>
        @if(auth()->user()->canWrite())
            <div class="d-flex gap-2">
                @if($task->nextStatusAction())
                    @php $next = $task->nextStatusAction(); @endphp
                    <form method="POST" action="{{ route('tasks.status', $task) }}">
                        @csrf @method('PATCH')
                        <input type="hidden" name="status" value="{{ $next['status'] }}">
                        <button class="btn btn-fst-primary btn-sm"><i class="bi {{ $next['icon'] }} me-1"></i>{{ $next['label'] }}</button>
                    </form>
                @endif
                <button class="btn btn-light border btn-sm" data-bs-toggle="modal" data-bs-target="#editTaskModal"><i class="bi bi-pencil me-1"></i>Edit</button>
                <form method="POST" action="{{ route('tasks.destroy', $task) }}" onsubmit="fstConfirm(event, 'Delete task \'{{ $task->title }}\'?')">@csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash"></i></button>
                </form>
            </div>
        @endif
    </div>
    @if($task->description)
        <div class="border-top mt-3 pt-3 small">{{ $task->description }}</div>
    @endif
</div>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="fst-card p-4">
            <h6 class="fw-bold text-body-secondary text-uppercase mb-3" style="font-size:.72rem;letter-spacing:.06em"><i class="bi bi-chat-left-text me-1"></i>Developer Notes</h6>
            @include('admin.tasks._notes')
        </div>
    </div>

    <div class="col-lg-5">
        <div class="fst-card p-4 mb-3">
            <h6 class="fw-bold text-body-secondary text-uppercase mb-3" style="font-size:.72rem;letter-spacing:.06em">Details</h6>
            <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Assignee</span><strong>{{ $task->assignee?->name ?? '—' }}</strong></div>
            <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Created by</span><strong>{{ $task->createdBy?->name ?? '—' }}</strong></div>
            <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Created</span><strong>{{ $task->created_at->format('M j, Y H:i') }}</strong></div>
            <div class="d-flex justify-content-between border-bottom py-2 small"><span class="text-body-secondary">Last update</span><strong>{{ $task->updated_at->format('M j, Y H:i') }}</strong></div>
            @if($task->completed_at)
                <div class="d-flex justify-content-between py-2 small"><span class="text-body-secondary">Completed</span><strong class="text-success">{{ $task->completed_at->format('M j, Y H:i') }}</strong></div>
            @endif
        </div>

        <div class="fst-card p-4">
            <h6 class="fw-bold text-body-secondary text-uppercase mb-3" style="font-size:.72rem;letter-spacing:.06em"><i class="bi bi-clock-history me-1"></i>History</h6>
            @forelse($task->activities as $activity)
                <div class="d-flex gap-2 pb-3 {{ !$loop->last ? 'border-bottom mb-3' : '' }}">
                    <span class="avatar avatar-sm flex-shrink-0" style="background:linear-gradient(135deg,#6366f1,#a855f7)">
                        {{ strtoupper(substr($activity->user?->name ?? 'S', 0, 1)) }}
                    </span>
                    <div class="small flex-grow-1">
                        <span class="fst-pill {{ [
                                'created' => 'pill-info', 'status' => 'pill-primary',
                                'note_done' => 'pill-success', 'note_reopened' => 'pill-warning',
                                'note_deleted' => 'pill-danger',
                            ][$activity->type] ?? 'pill-secondary' }}" style="font-size:.65rem">{{ $activity->typeLabel() }}</span>
                        <span class="ms-1">{{ $activity->message }}</span>
                        <div class="text-body-secondary" style="font-size:.72rem">{{ $activity->created_at->format('M j, Y H:i') }}</div>
                    </div>
                </div>
            @empty
                <p class="small text-body-secondary mb-0">No history yet</p>
            @endforelse
        </div>
    </div>
</div>

@if(auth()->user()->canWrite())
<div class="modal fade" id="editTaskModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Task</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('tasks.update', $task) }}" id="editTaskForm">
                @csrf @method('PUT')
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">System</label>
                        <select name="system_id" class="form-select">
                            @foreach($systems as $s)<option value="{{ $s->id }}" {{ $task->system_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>@endforeach
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
                <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-fst-primary" form="editTaskForm">Save</button></div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
