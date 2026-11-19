@if(auth()->user()->canWrite() && !$system->trashed())
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#taskModal"><i class="bi bi-plus-lg me-1"></i>Add Task</button>
</div>
@endif

<div class="table-responsive">
    <table class="table fst-table mb-0">
        <thead><tr><th>Task</th><th>Status</th><th>Priority</th><th>Assignee</th><th>Due</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse($tasks as $task)
            <tr>
                <td>
                    <a href="{{ route('tasks.show', $task) }}" class="fw-semibold link-plain {{ $task->status === 'done' ? 'text-decoration-line-through text-body-secondary' : '' }}">{{ $task->title }}</a>
                    @if($task->notes->count())
                        @php $checkableCount = $task->notes->whereIn('type', \App\Models\TaskNote::CHECKABLE_TYPES)->count(); @endphp
                        <span class="fst-pill {{ $task->notesRemaining() ? 'pill-primary' : 'pill-success' }}" title="Developer notes: {{ $checkableCount - $task->notesRemaining() }}/{{ $checkableCount }} done">
                            <i class="bi bi-chat-left-text" style="font-size:.7rem"></i>{{ $checkableCount - $task->notesRemaining() }}/{{ $checkableCount }}
                        </span>
                    @endif
                    @if($task->description)<small class="d-block text-body-secondary">{{ \Illuminate\Support\Str::limit($task->description, 90) }}</small>@endif
                </td>
                <td><x-status-pill :status="$task->status" /></td>
                <td><x-status-pill :status="$task->priority" /></td>
                <td><small>{{ $task->assignee?->name ?? '—' }}</small></td>
                <td><small class="{{ $task->due_date && $task->status !== 'done' && $task->due_date->isPast() ? 'text-danger fw-semibold' : '' }}">{{ $task->due_date?->format('M j') ?? '—' }}</small></td>
                <td class="text-end">
                    @if(auth()->user()->canWrite())
                        @if($task->nextStatusAction())
                            @php $next = $task->nextStatusAction(); @endphp
                            <form method="POST" action="{{ route('tasks.status', $task) }}" class="d-inline">
                                @csrf @method('PATCH')
                                <input type="hidden" name="status" value="{{ $next['status'] }}">
                                <button class="btn btn-sm {{ $task->allNotesDone() ? 'btn-fst-primary' : 'btn-light border text-body-secondary' }}" title="{{ $next['label'] }}">
                                    <i class="bi {{ $next['icon'] }}"></i>
                                </button>
                            </form>
                        @endif
                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editTaskModal{{ $task->id }}"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('tasks.destroy', $task) }}" class="d-inline" onsubmit="fstConfirm(event, 'Delete task \'{{ $task->title }}\'?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center py-4 text-body-secondary">No tasks yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if(auth()->user()->canWrite())
<div class="modal fade" id="taskModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Add Task</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('tasks.store') }}">
                @csrf
                <input type="hidden" name="system_id" value="{{ $system->id }}">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
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
                    <button class="btn btn-fst-primary">Add Task</button></div>
            </form>
        </div>
    </div>
</div>

@foreach($tasks as $task)
<div class="modal fade" id="editTaskModal{{ $task->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Task</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('tasks.update', $task) }}" id="editTaskForm{{ $task->id }}">
                @csrf @method('PUT')
                <input type="hidden" name="system_id" value="{{ $system->id }}">
                <div class="modal-body row g-3">
                    <div class="col-12"><label class="form-label small fw-semibold">Title</label>
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
@endif
