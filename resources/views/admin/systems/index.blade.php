@extends('layouts.app')

@section('title', 'Systems')

@php
$activeStatus = request()->query('status');
$activeStack = request()->query('stack');
$activeEnv = request()->query('environment');
$showAll = request()->query('all') === '1';
$view = $view ?? 'grid';
@endphp

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Systems</h4>
        <small class="text-body-secondary">{{ $systems->total() }} systems registered</small>
    </div>
    <div class="d-flex gap-2">
        <div class="btn-group btn-group-sm">
            <a class="btn {{ $view === 'grid' ? 'btn-fst-primary' : 'btn-light border' }}" href="{{ request()->fullUrlWithQuery(['view' => 'grid']) }}"><i class="bi bi-grid-3x3-gap-fill"></i></a>
            <a class="btn {{ $view === 'table' ? 'btn-fst-primary' : 'btn-light border' }}" href="{{ request()->fullUrlWithQuery(['view' => 'table']) }}"><i class="bi bi-list-ul"></i></a>
        </div>
        @if(auth()->user()->canWrite())
            <a href="{{ route('systems.create') }}" class="btn btn-fst-primary btn-sm"><i class="bi bi-plus-lg me-1"></i>New System</a>
        @endif
    </div>
</div>

{{-- Filters --}}
<div class="fst-card p-3 mb-3 d-flex flex-wrap gap-2 align-items-center">
    <form method="GET" action="{{ route('systems.index') }}" class="d-flex flex-wrap gap-2 align-items-center">
        @if($view) <input type="hidden" name="view" value="{{ $view }}"> @endif
        @if($showAll) <input type="hidden" name="all" value="1"> @endif
        <div class="input-group input-group-sm" style="width:240px">
            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control border-start-0 ps-0" placeholder="Search..." value="{{ request()->query('q') }}">
        </div>
        <select name="status" class="form-select form-select-sm" style="width:auto">
            <option value="">All statuses</option>
            @foreach(\App\Models\System::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ $activeStatus === $key ? 'selected' : '' }}>{{ $label }} ({{ $counts['status'][$key] ?? 0 }})</option>
            @endforeach
        </select>
        <select name="stack" class="form-select form-select-sm" style="width:auto">
            <option value="">All stacks</option>
            @foreach(\App\Models\System::STACKS as $key => $label)
                <option value="{{ $key }}" {{ $activeStack === $key ? 'selected' : '' }}>{{ $label }} ({{ $counts['stack'][$key] ?? 0 }})</option>
            @endforeach
        </select>
        <select name="environment" class="form-select form-select-sm" style="width:auto">
            <option value="">All environments</option>
            @foreach(\App\Models\System::ENVIRONMENTS as $key => $label)
                <option value="{{ $key }}" {{ $activeEnv === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
        <button class="btn btn-light border btn-sm">Filter</button>
        <a href="{{ route('systems.index', ['view' => $view]) }}" class="btn btn-light border btn-sm" title="Clear filters"><i class="bi bi-x-lg"></i></a>
    </form>
    <div class="form-check form-switch ms-auto small">
        <input class="form-check-input" type="checkbox" id="showAllSwitch" {{ $showAll ? 'checked' : '' }}
               onchange="window.location='{{ route('systems.index', array_merge(request()->except(['all']), ['all' => $showAll ? '' : '1'])) }}'">
        <label class="form-check-label" for="showAllSwitch">Show archived</label>
    </div>
</div>

@if($view === 'grid')
    <div class="row g-3">
        @forelse($systems as $system)
            <div class="col-md-6 col-xl-4">
                <a href="{{ route('systems.show', $system) }}" class="link-plain">
                    <div class="fst-card fst-card-hover p-3 h-100 {{ $system->trashed() ? 'opacity-75' : '' }}">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <span class="system-avatar" style="background:linear-gradient(135deg,#6366f1,#a855f7)">
                                <i class="bi {{ $system->icon ?: 'bi-hdd-stack-fill' }}"></i>
                            </span>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-bold text-truncate">{{ $system->name }}</div>
                                <small class="text-body-secondary">{{ $system->stackLabel() }} · {{ $system->environmentLabel() }}</small>
                            </div>
                            @if($system->trashed())
                                <x-status-pill status="archived" label="Archived" />
                            @else
                                <x-status-pill :status="$system->status" />
                            @endif
                        </div>
                        <p class="small text-body-secondary mb-3" style="min-height:2.6em">{{ Str::limit($system->description, 110) }}</p>
                        <div class="d-flex justify-content-between align-items-center border-top pt-2">
                            <small class="text-body-secondary">
                                <i class="bi bi-tag me-1"></i>{{ $system->latestVersion?->version ?? '—' }}
                                <i class="bi bi-kanban ms-2 me-1"></i>{{ $system->open_tasks_count }} open
                            </small>
                            <i class="bi bi-arrow-right"></i>
                        </div>
                    </div>
                </a>
            </div>
        @empty
            <div class="col-12">
                <div class="fst-card text-center py-5 text-body-secondary">
                    <i class="bi bi-hdd-stack" style="font-size:2.5rem"></i>
                    <p class="mt-2 mb-3">No systems match your filters</p>
                    @if(auth()->user()->canWrite())
                        <a href="{{ route('systems.create') }}" class="btn btn-fst-primary btn-sm">Add your first system</a>
                    @endif
                </div>
            </div>
        @endforelse
    </div>
@else
    <div class="fst-card p-0 overflow-hidden">
        <div class="table-responsive">
            <table class="table fst-table mb-0">
                <thead><tr>
                    <th>System</th><th>Stack</th><th>Status</th><th>Environment</th>
                    <th>Latest version</th><th>Open tasks</th><th>Owner</th><th class="text-end">Actions</th>
                </tr></thead>
                <tbody>
                @forelse($systems as $system)
                    <tr class="{{ $system->trashed() ? 'opacity-75' : '' }}">
                        <td>
                            <a href="{{ route('systems.show', $system) }}" class="link-plain d-flex align-items-center gap-2">
                                <span class="system-avatar" style="width:34px;height:34px;flex:0 0 34px;font-size:.95rem;background:linear-gradient(135deg,#6366f1,#a855f7)">
                                    <i class="bi {{ $system->icon ?: 'bi-hdd-stack-fill' }}"></i>
                                </span>
                                <div>
                                    <div class="fw-semibold">{{ $system->name }}</div>
                                    <small class="text-body-secondary">{{ Str::limit($system->description, 60) }}</small>
                                </div>
                            </a>
                        </td>
                        <td><small>{{ $system->stackLabel() }}</small></td>
                        <td><x-status-pill :status="$system->trashed() ? 'archived' : $system->status" /></td>
                        <td><x-status-pill :status="$system->environment" /></td>
                        <td><small>{{ $system->latestVersion?->version ?? '—' }}</small></td>
                        <td><small>{{ $system->open_tasks_count }}</small></td>
                        <td><small>{{ $system->owner?->name ?? '—' }}</small></td>
                        <td class="text-end">
                            @if($system->trashed())
                                @if(auth()->user()->canWrite())
                                <form method="POST" action="{{ route('systems.restore', $system->id) }}" class="d-inline">@csrf
                                    <button class="btn btn-sm btn-light border" title="Restore"><i class="bi bi-arrow-counterclockwise"></i></button>
                                </form>
                                @endif
                                @if(auth()->user()->isAdmin())
                                <form method="POST" action="{{ route('systems.force-delete', $system->id) }}" class="d-inline" onsubmit="fstConfirm(event, 'Permanently delete \'{{ $system->name }}\'?')">@csrf @method('DELETE')
                                    <button class="btn btn-sm btn-outline-danger" title="Delete forever"><i class="bi bi-trash-fill"></i></button>
                                </form>
                                @endif
                            @else
                                @if(auth()->user()->canWrite())
                                    <a href="{{ route('systems.invoice', $system) }}" target="_blank" class="btn btn-sm btn-light border" title="Invoice"><i class="bi bi-receipt"></i></a>
                                    <a href="{{ route('systems.edit', $system) }}" class="btn btn-sm btn-light border" title="Edit"><i class="bi bi-pencil"></i></a>
                                @endif
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center py-5 text-body-secondary">No systems found</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-body-secondary">Showing {{ $systems->firstItem() ?? 0 }}–{{ $systems->lastItem() ?? 0 }} of {{ $systems->total() }}</small>
    {{ $systems->appends(request()->query())->links() }}
</div>
@endsection
