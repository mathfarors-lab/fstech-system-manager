@extends('layouts.guest')

@section('content')
<!-- Navbar -->
<nav class="navbar navbar-expand-lg fixed-top shadow-sm bg-white">
    <div class="container">
        <a class="navbar-brand fw-bold text-primary d-flex align-items-center" href="#">
            <i class="bi bi-rocket-takeoff-fill me-2 fs-4"></i> FS Tech
        </a>
        <div class="ms-auto">
            <a href="{{ route('login') }}" class="btn btn-outline-primary rounded-pill px-4">Admin Login</a>
        </div>
    </div>
</nav>

<!-- Hero Section -->
<header class="hero text-center py-5 mt-5">
    <div class="container py-5">
        <h1 class="fw-bold display-3 mb-3">FS Tech System Manager</h1>
        <p class="lead text-muted mb-5">Professional ecosystem for project tracking, financial insights, and automated operations.</p>
        <a href="#projects" class="btn btn-primary btn-lg rounded-pill px-5 shadow">Explore Projects</a>
    </div>
</header>

<!-- Projects Section -->
<section id="projects" class="py-5 bg-light">
    <div class="container">
        <h2 class="fw-bold text-center mb-5">Active Projects</h2>
        <div class="row g-4">
            @foreach(\App\Models\System::where('status', 'active')->get() as $system)
                <div class="col-md-4">
                    <div class="card h-100 border-0 shadow-sm p-4 rounded-4">
                        <div class="d-flex justify-content-between mb-3">
                            <span class="badge bg-primary-subtle text-primary">{{ ucfirst($system->stack) }}</span>
                            <i class="bi bi-check-circle-fill text-success"></i>
                        </div>
                        <h5 class="fw-bold">{{ $system->name }}</h5>
                        <p class="text-muted small flex-grow-1">{{ Str::limit($system->description ?? 'No description provided.', 80) }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- Contact Section -->
<section class="py-5">
    <div class="container text-center">
        <div class="card border-0 shadow-sm p-5 rounded-4 bg-primary text-white">
            <h2 class="fw-bold mb-4">Let's Connect</h2>
            <p class="lead mb-4">Questions about our systems or development services? Reach out to our lead administrator.</p>
            <div class="d-flex justify-content-center gap-4">
                <div>
                    <i class="bi bi-person-badge fs-3"></i>
                    <p class="mb-0 fw-bold">FS Tech Admin</p>
                </div>
                <div>
                    <i class="bi bi-envelope-at fs-3"></i>
                    <p class="mb-0">admin@fstech.com</p>
                </div>
            </div>
        </div>
    </div>
</section>

<footer class="py-4 text-center text-muted">
    <small>&copy; {{ now()->year }} FS Tech System Manager. All rights reserved.</small>
</footer>
@endsection
