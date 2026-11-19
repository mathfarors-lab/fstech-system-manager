<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0c0f1e">
    <title>@yield('title', 'Dashboard') · {{ config('app.name') }}</title>
    @if(is_file(public_path('images/fs-tech-logo.png')))
        <link rel="icon" type="image/png" href="{{ asset('images/fs-tech-logo.png') }}">
        <link rel="apple-touch-icon" href="{{ asset('images/fs-tech-logo.png') }}">
    @endif
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="{{ asset('css/theme.css') }}" rel="stylesheet">
    <script>
        (function () {
            var stored = localStorage.getItem('fst-theme');
            if (stored) {
                document.documentElement.setAttribute('data-bs-theme', stored);
            } else if (window.matchMedia('(prefers-color-scheme: dark)').matches) {
                document.documentElement.setAttribute('data-bs-theme', 'dark');
            }
        })();
    </script>
    @stack('styles')
</head>
<body>
<div class="fst-backdrop" id="fstBackdrop"></div>

{{-- Sidebar --}}
<nav class="fst-sidebar" id="fstSidebar">
    <a href="{{ route('dashboard') }}" class="fst-sidebar-brand">
        <span class="fst-brand-mark" aria-hidden="true">FS</span>
        <span class="fst-brand-text">
            <strong>FS Tech</strong>
            <small>System Manager</small>
        </span>
    </a>

    <p class="fst-group-label">Workspace</p>
    <ul class="nav flex-column flex-grow-1">
        <li><a class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}" href="{{ route('dashboard') }}">
            <i class="bi bi-speedometer2"></i><span>Dashboard</span></a></li>
        <li><a class="nav-link {{ request()->routeIs('systems.*') ? 'active' : '' }}" href="{{ route('systems.index') }}">
            <i class="bi bi-hdd-stack"></i><span>Systems</span></a></li>
        <li><a class="nav-link {{ request()->routeIs('tasks.index') ? 'active' : '' }}" href="{{ route('tasks.index') }}">
            <i class="bi bi-kanban"></i><span>Tasks</span></a></li>
        <li><a class="nav-link {{ request()->routeIs('logs.index') ? 'active' : '' }}" href="{{ route('logs.index') }}">
            <i class="bi bi-clock-history"></i><span>Ops Logs</span></a></li>
        <li><a class="nav-link {{ request()->routeIs('finance.*') ? 'active' : '' }}" href="{{ route('finance.dashboard') }}">
            <i class="bi bi-cash-stack"></i><span>Finance</span></a></li>
        @if(auth()->user()->isAdmin())
        <li><a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">
            <i class="bi bi-people"></i><span>Users</span></a></li>
        @endif
    </ul>

    <div class="fst-user-card d-flex align-items-center gap-2">
        <span class="avatar">{{ strtoupper(substr(auth()->user()->name, 0, 1)) }}</span>
        <div class="fst-user-meta flex-grow-1 overflow-hidden">
            <div class="small fw-semibold text-white text-truncate">{{ auth()->user()->name }}</div>
            <div class="text-white-50" style="font-size:.7rem">{{ auth()->user()->roleLabel() }}</div>
        </div>
    </div>
</nav>

{{-- Main --}}
<div class="fst-main" id="fstMain">
    <header class="fst-topbar">
        <button type="button" class="fst-icon-btn" onclick="fstToggleSidebar()" aria-label="Toggle sidebar">
            <i class="bi bi-list"></i>
        </button>

        <div class="fst-topbar-title d-none d-lg-flex" aria-hidden="true">
            <span class="fst-crumb">Workspace</span>
            <span class="fst-current">@yield('title', 'Dashboard')</span>
        </div>

        <form class="fst-search d-none d-md-block" method="GET" action="{{ route('systems.index') }}">
            <i class="bi bi-search"></i>
            <input type="search" name="q" class="fst-search-input" placeholder="Search systems..."
                   value="{{ request()->query('q') }}" autocomplete="off" aria-label="Search systems">
        </form>

        <div class="ms-auto d-flex align-items-center gap-2">
            <button type="button" class="fst-icon-btn" onclick="fstToggleTheme()" aria-label="Toggle theme">
                <i class="bi bi-moon-stars-fill" id="themeIcon"></i>
            </button>

            <div class="dropdown">
                <button class="fst-user-btn" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Account menu">
                    <span class="avatar avatar-sm" style="background:linear-gradient(135deg,#6366f1,#a855f7)">
                        {{ strtoupper(substr(auth()->user()->name, 0, 1)) }}
                    </span>
                    <span class="d-none d-lg-inline small fw-semibold">{{ auth()->user()->name }}</span>
                    <i class="bi bi-chevron-down small text-body-secondary"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end shadow-sm">
                    <li class="dropdown-header">{{ auth()->user()->email }}</li>
                    <li><a class="dropdown-item" href="{{ route('profile.edit') }}"><i class="bi bi-person me-2"></i>Profile</a></li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <form method="POST" action="{{ route('logout') }}">@csrf
                            <button class="dropdown-item text-danger"><i class="bi bi-box-arrow-right me-2"></i>Logout</button>
                        </form>
                    </li>
                </ul>
            </div>
        </div>
    </header>

    <main class="fst-page">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show alert-auto" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i>{{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show alert-auto" role="alert">
                <i class="bi bi-x-circle-fill me-2"></i>{{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        @endif

        @yield('content')
    </main>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.2/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
<script src="{{ asset('js/app.js') }}"></script>
@stack('scripts')
</body>
</html>
