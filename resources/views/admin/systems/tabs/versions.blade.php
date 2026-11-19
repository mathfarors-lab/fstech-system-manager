@if(auth()->user()->canWrite() && !$system->trashed())
<div class="d-flex justify-content-end mb-3">
    <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#versionModal"><i class="bi bi-plus-lg me-1"></i>Add Version</button>
</div>
@endif

<div class="table-responsive">
    <table class="table fst-table mb-0">
        <thead><tr><th>Version</th><th>Release date</th><th>Changelog</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse($versions as $version)
            <tr>
                <td><span class="fw-semibold">{{ $version->version }}</span></td>
                <td><small>{{ $version->release_date?->format('M j, Y') ?? '—' }}</small></td>
                <td><small class="text-body-secondary">{{ \Illuminate\Support\Str::limit($version->changelog, 120) ?? '—' }}</small></td>
                <td class="text-end">
                    @if(auth()->user()->canWrite())
                        <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editVersionModal{{ $version->id }}"><i class="bi bi-pencil"></i></button>
                        <form method="POST" action="{{ route('systems.versions.destroy', [$system, $version]) }}" class="d-inline" onsubmit="fstConfirm(event, 'Delete version {{ $version->version }}?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="4" class="text-center py-4 text-body-secondary">No versions recorded</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if(auth()->user()->canWrite())
<div class="modal fade" id="versionModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Add Version</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('systems.versions.store', $system) }}">
                @csrf
                <div class="modal-body row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Version <span class="text-danger">*</span></label>
                        <input type="text" name="version" class="form-control" placeholder="1.0.0" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-semibold">Release date</label>
                        <input type="date" name="release_date" class="form-control" value="{{ date('Y-m-d') }}">
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-semibold">Changelog</label>
                        <textarea name="changelog" rows="5" class="form-control" placeholder="What changed in this release?"></textarea>
                    </div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-fst-primary">Add Version</button></div>
            </form>
        </div>
    </div>
</div>

@foreach($versions as $version)
<div class="modal fade" id="editVersionModal{{ $version->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Version {{ $version->version }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <form method="POST" action="{{ route('systems.versions.update', [$system, $version]) }}">
                @csrf @method('PUT')
                <div class="modal-body row g-3">
                    <div class="col-md-4"><label class="form-label small fw-semibold">Version</label>
                        <input type="text" name="version" class="form-control" value="{{ old('version', $version->version) }}" required></div>
                    <div class="col-md-4"><label class="form-label small fw-semibold">Release date</label>
                        <input type="date" name="release_date" class="form-control" value="{{ old('release_date', $version->release_date?->format('Y-m-d')) }}"></div>
                    <div class="col-12"><label class="form-label small fw-semibold">Changelog</label>
                        <textarea name="changelog" rows="5" class="form-control">{{ old('changelog', $version->changelog) }}</textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
                    <button class="btn btn-fst-primary">Save</button></div>
            </form>
        </div>
    </div>
</div>
@endforeach
@endif
