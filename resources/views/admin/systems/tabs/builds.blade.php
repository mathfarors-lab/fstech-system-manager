@if(auth()->user()->canWrite() && !$system->trashed())
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#buildModal"><i class="bi bi-plus-lg me-1"></i>Record Build</button>
</div>
@endif

<div class="table-responsive">
    <table class="table fst-table mb-0">
        <thead><tr><th>Version</th><th>Environment</th><th>Status</th><th>Built by</th><th>Built</th><th>Deployed</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse($builds as $build)
            <tr>
                <td><span class="fw-semibold">{{ $build->version_label }}</span></td>
                <td><x-status-pill :status="$build->environment" /></td>
                <td><x-status-pill :status="$build->status" /></td>
                <td><small>{{ $build->builtBy?->name ?? '—' }}</small></td>
                <td><small>{{ $build->built_at?->format('M j, g:ia') ?? '—' }}</small></td>
                <td><small>{{ $build->deployed_at?->format('M j, g:ia') ?? '—' }}</small></td>
                <td class="text-end">
                    @if(auth()->user()->canWrite())
                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editBuildModal{{ $build->id }}" title="Update status"><i class="bi bi-arrow-repeat"></i></button>
                        <form method="POST" action="{{ route('systems.builds.destroy', [$system, $build]) }}" class="d-inline" onsubmit="fstConfirm(event, 'Delete build {{ $build->version_label }}?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="7" class="text-center py-4 text-body-secondary">No builds recorded</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($builds->hasPages())
<div class="mt-3 d-flex justify-content-end">{{ $builds->appends(['tab' => 'builds'])->links() }}</div>
@endif

@if(auth()->user()->canWrite())
<div class="modal fade" id="buildModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Record Build</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('systems.builds.store', $system) }}">
                @csrf
                <div class="modal-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Version label <span class="text-danger">*</span></label>
                        <input type="text" name="version_label" class="form-control" placeholder="1.0.0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Link to version</label>
                        <select name="version_id" class="form-select">
                            <option value="">— none —</option>
                            @foreach($versions as $v)
                                <option value="{{ $v->id }}">{{ $v->version }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Environment</label>
                        <select name="environment" class="form-select">
                            @foreach(\App\Models\System::ENVIRONMENTS as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Models\Build::STATUSES as $key => $label)
                                <option value="{{ $key }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="3" class="form-control"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-fst-primary">Record Build</button></div>
            </form>
        </div>
    </div>
</div>

@foreach($builds as $build)
<div class="modal fade" id="editBuildModal{{ $build->id }}" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Update Build {{ $build->version_label }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('systems.builds.update', [$system, $build]) }}">
                @csrf @method('PUT')
                <div class="modal-body row g-3">
                    <div class="col-md-6"><label class="form-label small fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            @foreach(\App\Models\Build::STATUSES as $key => $label)
                                <option value="{{ $key }}" {{ $build->status === $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Notes</label>
                        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $build->notes) }}</textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-fst-primary">Update</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif
