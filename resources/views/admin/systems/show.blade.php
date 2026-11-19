@extends('layouts.app')

@section('title', $system->name)

@section('content')
<div class="fst-card p-4 mb-3">
    <div class="d-flex flex-wrap align-items-center gap-3">
        <span class="system-avatar" style="width:52px;height:52px;flex:0 0 52px;font-size:1.4rem;background:linear-gradient(135deg,#6366f1,#a855f7)">
            <i class="bi {{ $system->icon ?: 'bi-hdd-stack-fill' }}"></i>
        </span>
        <div class="flex-grow-1">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <h4 class="fw-bold mb-0">{{ $system->name }}</h4>
                @if($system->trashed())
                    <x-status-pill status="archived" label="Archived" />
                @else
                    <x-status-pill :status="$system->status" />
                @endif
                <x-status-pill :status="$system->environment" />
            </div>
            <div class="small text-body-secondary mt-1">
                <i class="bi bi-cpu me-1"></i>{{ $system->stackLabel() }}
                @if($system->folder_path)<i class="bi bi-folder2 ms-3 me-1"></i><code>{{ $system->folder_path }}</code>@endif
                @if($system->repo_url)<a class="ms-3 link-plain" href="{{ $system->repo_url }}" target="_blank"><i class="bi bi-github me-1"></i>Repo</a>@endif
                @if($system->deploy_url)<a class="ms-3 link-plain" href="{{ $system->deploy_url }}" target="_blank"><i class="bi bi-browser me-1"></i>Live</a>@endif
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($system->trashed())
                @if(auth()->user()->canWrite())
                <form method="POST" action="{{ route('systems.restore', $system->id) }}">@csrf
                    <button class="btn btn-light border btn-sm"><i class="bi bi-arrow-counterclockwise me-1"></i>Restore</button>
                </form>
                @endif
                @if(auth()->user()->isAdmin())
                <form method="POST" action="{{ route('systems.force-delete', $system->id) }}" onsubmit="fstConfirm(event, 'Permanently delete \'{{ $system->name }}\'?')">@csrf @method('DELETE')
                    <button class="btn btn-outline-danger btn-sm"><i class="bi bi-trash-fill"></i></button>
                </form>
                @endif
            @else
                @if(auth()->user()->canWrite())
                    <a href="{{ route('systems.invoice', $system) }}" target="_blank" class="btn btn-light border btn-sm" title="Print or save invoice as PDF"><i class="bi bi-receipt me-1"></i>Invoice</a>
                    <a href="{{ route('systems.edit', $system) }}" class="btn btn-light border btn-sm"><i class="bi bi-pencil me-1"></i>Edit</a>
                    <form method="POST" action="{{ route('systems.destroy', $system) }}" onsubmit="fstConfirm(event, 'Archive system \'{{ $system->name }}\'?')">@csrf @method('DELETE')
                        <button class="btn btn-outline-danger btn-sm"><i class="bi bi-archive"></i></button>
                    </form>
                @endif
            @endif
        </div>
    </div>
    @if($system->notes)
        <div class="border-top mt-3 pt-3 small text-body-secondary"><i class="bi bi-sticky me-1"></i>{{ $system->notes }}</div>
    @endif
</div>

<ul class="nav fst-tabs mb-0 gap-1" style="border-bottom:1px solid var(--bs-border-color)">
    @foreach(['overview' => 'bi-info-circle', 'versions' => 'bi-tag', 'builds' => 'bi-rocket-takeoff', 'tasks' => 'bi-kanban', 'logs' => 'bi-clock-history', 'finance' => 'bi-cash-stack'] as $key => $icon)
        <li class="nav-item">
            <a class="nav-link {{ $tab === $key ? 'active fst-tab-active' : '' }} link-plain px-3 py-2 small fw-semibold"
               href="{{ route('systems.show', [$system, 'tab' => $key]) }}">
                <i class="bi {{ $icon }} me-1"></i>{{ ucfirst($key) }}
            </a>
        </li>
    @endforeach
</ul>

<div class="fst-card p-4 rounded-top-0">
    @switch($tab)
        @case('overview')
            @include('admin.systems.tabs.overview')
            @break
        @case('versions')
            @include('admin.systems.tabs.versions')
            @break
        @case('builds')
            @include('admin.systems.tabs.builds')
            @break
        @case('tasks')
            @include('admin.systems.tabs.tasks')
            @break
        @case('logs')
            @include('admin.systems.tabs.logs')
            @break
        @case('finance')
            @include('admin.systems.tabs.finance')
            @break
    @endswitch
</div>
@endsection
