@php
$canWrite = auth()->user()->canWrite();
$checkableNotes = $task->notes->whereIn('type', \App\Models\TaskNote::CHECKABLE_TYPES);
$doneCount = $checkableNotes->where('is_done', true)->count();
$total = $checkableNotes->count();
$hasAny = $task->notes->isNotEmpty();
@endphp
@if($hasAny || $canWrite)
<div class="fst-notes mt-2 pt-2 border-top">
    <div class="d-flex align-items-center justify-content-between mb-1">
        <small class="text-body-secondary fw-semibold"><i class="bi bi-chat-left-text me-1"></i>Developer notes</small>
        @if($total)
            <small class="fst-note-progress {{ $doneCount === $total ? 'text-success fw-semibold' : 'text-body-secondary' }}">
                {{ $doneCount }}/{{ $total }}
                @if($doneCount === $total)<i class="bi bi-check2-circle"></i>@endif
            </small>
        @endif
    </div>

    @if($total)
        <div class="progress fst-note-bar mt-1" style="height:4px">
            <div class="progress-bar {{ $doneCount === $total ? 'bg-success' : '' }}" role="progressbar"
                 style="width: {{ (int) round($doneCount / $total * 100) }}%"></div>
        </div>
    @endif

    <div class="fst-notes-list">
        @foreach($task->notes as $note)
            @php $checkable = $note->isCheckable(); @endphp
            <div>
                <div class="d-flex align-items-start gap-2 fst-note-row">
                    @if($canWrite && $checkable)
                        <form method="POST" action="{{ route('notes.toggle', $note) }}" class="flex-shrink-0">
                            @csrf @method('PATCH')
                            <input type="checkbox" class="form-check-input fst-note-check" {{ $note->is_done ? 'checked' : '' }}
                                   onchange="this.form.submit()" title="Mark as {{ $note->is_done ? 'not done' : 'done' }}">
                        </form>
                    @elseif($checkable)
                        <i class="bi {{ $note->is_done ? 'bi-check-square-fill text-success' : 'bi-square text-body-tertiary' }} small mt-1"></i>
                    @else
                        <i class="bi {{ $note->type === 'prompt' ? 'bi-lightbulb' : 'bi-chat-dots' }} small mt-1 {{ $note->type === 'prompt' ? 'text-warning' : 'text-body-tertiary' }}"></i>
                    @endif

                    <span class="small flex-grow-1 fst-note-body {{ $note->is_done && $checkable ? 'text-decoration-line-through text-body-tertiary' : '' }}">
                        @if($note->type !== 'note')
                            <span class="fst-pill {{ $note->type === 'prompt' ? 'pill-primary' : 'pill-secondary' }}" style="font-size:.65rem">{{ $note->typeLabel() }}</span>
                        @endif
                        {{ $note->body }}
                    </span>

                    @if($canWrite)
                        <div class="flex-shrink-0 d-flex align-items-center">
                            <button class="btn btn-link p-0 fst-note-delete" type="button" data-bs-toggle="collapse"
                                    data-bs-target="#noteEdit{{ $note->id }}" title="Edit note"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('notes.destroy', $note) }}" class="flex-shrink-0"
                                  onsubmit="fstConfirm(event, 'Delete note \'{{ \Illuminate\Support\Str::limit($note->body, 40) }}\'?')">
                                @csrf @method('DELETE')
                                <button class="btn btn-link p-0 fst-note-delete" title="Delete note"><i class="bi bi-x"></i></button>
                            </form>
                        </div>
                    @endif
                </div>

                @if($canWrite)
                    <div class="collapse {{ $errors->has('body') ? 'show' : '' }}" id="noteEdit{{ $note->id }}">
                        <form method="POST" action="{{ route('notes.update', $note) }}" class="d-flex gap-1 mt-1 mb-1" autocomplete="off">
                            @csrf @method('PATCH')
                            <select name="type" class="form-select form-select-sm" style="max-width:115px">
                                @foreach(\App\Models\TaskNote::TYPES as $key => $label)
                                    <option value="{{ $key }}" {{ $note->type === $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                            <input type="text" name="body" class="form-control form-control-sm" value="{{ $note->body }}" maxlength="2000" required>
                            <button class="btn btn-sm btn-fst-primary px-2" title="Save note"><i class="bi bi-check-lg"></i></button>
                        </form>
                    </div>
                @endif
            </div>
        @endforeach
    </div>

    @if($canWrite)
        <form method="POST" action="{{ route('tasks.notes.store', $task) }}" class="d-flex gap-1 mt-1" autocomplete="off">
            @csrf
            <select name="type" class="form-select form-select-sm" style="max-width:115px" title="Note type">
                @foreach(\App\Models\TaskNote::TYPES as $key => $label)
                    <option value="{{ $key }}">{{ $label }}</option>
                @endforeach
            </select>
            <input type="text" name="body" class="form-control form-control-sm fst-note-input" placeholder="Note..." maxlength="2000" required>
            <button class="btn btn-sm btn-light border" title="Add note"><i class="bi bi-plus-lg"></i></button>
        </form>
    @endif
</div>
@endif
