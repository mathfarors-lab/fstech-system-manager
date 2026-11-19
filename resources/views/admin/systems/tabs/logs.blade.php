@if(auth()->user()->canWrite() && !$system->trashed())
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#logModal"><i class="bi bi-plus-lg me-1"></i>Add Log Entry</button>
</div>
@endif

<div class="fst-timeline">
    @forelse($logs as $log)
        <div class="fst-timeline-item">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <x-status-pill :status="$log->type" />
                <strong class="small">{{ $log->title }}</strong>
                <small class="text-body-secondary">{{ $log->performed_at->format('M j, Y g:ia') }}</small>
                @if(auth()->user()->canWrite())
                    <form method="POST" action="{{ route('systems.logs.destroy', [$system, $log]) }}" class="ms-auto" onsubmit="fstConfirm(event, 'Delete this log entry?')">@csrf @method('DELETE')
                        <button class="btn btn-sm btn-light border"><i class="bi bi-trash text-danger"></i></button>
                    </form>
                @endif
            </div>
            @if($log->details)<div class="small text-body-secondary mt-1">{{ $log->details }}</div>@endif
            <div class="small text-body-secondary mt-1"><i class="bi bi-person me-1"></i>{{ $log->performedBy?->name ?? 'System' }}</div>
        </div>
    @empty
        <p class="text-body-secondary mb-0">No operation logs yet</p>
    @endforelse
</div>

@if($logs->hasPages())
<div class="mt-3 d-flex justify-content-end">{{ $logs->appends(['tab' => 'logs'])->links() }}</div>
@endif

@if(auth()->user()->canWrite())
<div class="modal fade" id="logModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Add Operation Log</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('systems.logs.store', $system) }}">
                @csrf
                <div class="modal-body row g-3">
                    <div class="col-md-5"><label class="form-label small fw-semibold">Type</label>
                        <select name="type" class="form-select">@foreach(\App\Models\OperationLog::TYPES as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach</select></div>
                    <div class="col-md-7"><label class="form-label small fw-semibold">Performed at</label>
                        <input type="datetime-local" name="performed_at" class="form-control" value="{{ old('performed_at', now()->format('Y-m-d\TH:i')) }}" required></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
                        <input type="text" name="title" class="form-control" required></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Details</label>
                        <textarea name="details" rows="3" class="form-control"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-fst-primary">Add Entry</button></div>
            </form>
        </div>
    </div>
</div>
@endif
