@extends('layouts.guest')

@section('title', 'FS Tech System Manager · Projects, releases & operations in one place')
@section('description', 'FS Tech System Manager keeps every project, release, task, and payment in sync. Explore the systems we build and maintain.')

@php
    $stackIcons = [
        'laravel' => 'bi-braces',
        'php' => 'bi-filetype-php',
        'dotnet' => 'bi-microsoft',
        'node' => 'bi-filetype-js',
        'python' => 'bi-filetype-py',
        'flutter' => 'bi-phone',
        'other' => 'bi-code-slash',
    ];

    $miniIconColors = ['indigo', 'violet', 'cyan', 'green'];
    $consoleSystems = $featuredSystems->take(4);

    $capabilities = [
        ['icon' => 'bi-hdd-stack-fill', 'title' => 'Project portfolio', 'text' => 'A single registry for every system, stack, environment, and deployment target we manage.'],
        ['icon' => 'bi-rocket-takeoff-fill', 'title' => 'Releases & builds', 'text' => 'Version history, build outcomes, and release notes tracked from first commit to production.'],
        ['icon' => 'bi-kanban-fill', 'title' => 'Task boards', 'text' => 'Priorities, due dates, and developer notes on a drag-and-drop board the whole team can follow.'],
        ['icon' => 'bi-clock-history', 'title' => 'Operations logs', 'text' => 'An auditable timeline of deployments, backups, incidents, and the people behind each change.'],
        ['icon' => 'bi-cash-coin', 'title' => 'Finance & invoices', 'text' => 'Milestones, maintenance cycles, invoices, and payment status kept alongside delivery.'],
        ['icon' => 'bi-shield-lock-fill', 'title' => 'Role-based access', 'text' => 'Admins, developers, and viewers each get exactly the access their work requires.'],
    ];

    $workflow = [
        ['icon' => 'bi-compass', 'title' => 'Plan', 'text' => 'Scope the system, agree the milestones, and map out payment phases before any code is written.'],
        ['icon' => 'bi-code-slash', 'title' => 'Build', 'text' => 'Track tasks, notes, builds, and versions in one shared workspace with full visibility.'],
        ['icon' => 'bi-cloud-arrow-up', 'title' => 'Ship', 'text' => 'Record every deployment and release per environment so the team always knows what is live.'],
        ['icon' => 'bi-shield-check', 'title' => 'Maintain', 'text' => 'Monitor operations, schedule maintenance, and keep invoices and support history current.'],
    ];
@endphp

@section('content')
{{-- Navigation --}}
<nav class="navbar navbar-expand-lg fixed-top lp-nav" id="lpNav">
    <div class="container">
        <a class="navbar-brand lp-brand" href="{{ route('welcome') }}" aria-label="FS Tech System Manager home">
            <x-brand-logo variant="mark" height="38" />
            <span class="lp-brand-text">
                <strong>FS Tech</strong>
                <small>System Manager</small>
            </span>
        </a>

        <button class="navbar-toggler lp-toggler" type="button" data-bs-toggle="collapse"
                data-bs-target="#lpNavMenu" aria-controls="lpNavMenu" aria-expanded="false" aria-label="Toggle navigation">
            <i class="bi bi-list"></i>
        </button>

        <div class="collapse navbar-collapse" id="lpNavMenu">
            <ul class="navbar-nav mx-auto lp-nav-links">
                <li class="nav-item"><a class="nav-link" href="#projects">Projects</a></li>
                <li class="nav-item"><a class="nav-link" href="#capabilities">Capabilities</a></li>
                <li class="nav-item"><a class="nav-link" href="#workflow">How we work</a></li>
                <li class="nav-item"><a class="nav-link" href="#contact">Contact</a></li>
            </ul>
            <div class="d-flex align-items-center gap-2 lp-nav-actions">
                <a href="{{ route('login') }}" class="btn lp-btn-ghost">
                    <i class="bi bi-shield-lock"></i>
                    Admin sign in
                </a>
            </div>
        </div>
    </div>
</nav>

<main id="main">
    {{-- Hero --}}
    <header class="lp-hero">
        <div class="container">
            <div class="row align-items-center g-5">
                <div class="col-lg-6">
                    <span class="lp-eyebrow">
                        <span class="lp-live-dot" aria-hidden="true"></span>
                        Live portfolio · {{ $stats['active'] }} active {{ Str::plural('project', $stats['active']) }}
                    </span>

                    <h1 class="lp-hero-title">
                        Build, ship, and maintain every system from <span>one command center.</span>
                    </h1>

                    <p class="lp-hero-lead">
                        FS Tech System Manager keeps projects, releases, tasks, and payments in sync — so our
                        team ships with clarity and you always know where things stand.
                    </p>

                    <div class="d-flex flex-wrap gap-3">
                        <a href="#projects" class="btn lp-btn-primary btn-lg">
                            Explore projects
                            <i class="bi bi-arrow-down-short"></i>
                        </a>
                        <a href="{{ route('login') }}" class="btn lp-btn-outline btn-lg">
                            <i class="bi bi-shield-lock"></i>
                            Admin sign in
                        </a>
                    </div>

                    <div class="lp-hero-stats">
                        <div class="lp-hero-stat">
                            <strong>{{ number_format($stats['active']) }}</strong>
                            <span>Active systems</span>
                        </div>
                        <div class="lp-hero-stat">
                            <strong>{{ number_format($stats['stacks']) }}</strong>
                            <span>Tech {{ Str::plural('stack', $stats['stacks']) }}</span>
                        </div>
                        <div class="lp-hero-stat">
                            <strong>{{ number_format($stats['releases']) }}</strong>
                            <span>Releases tracked</span>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="lp-console" role="img" aria-label="Preview of the FS Tech System Manager dashboard">
                        <div class="lp-console-glow" aria-hidden="true"></div>

                        <div class="lp-window">
                            <div class="lp-window-bar">
                                <span></span><span></span><span></span>
                                <div class="lp-window-url">fstech / system-manager</div>
                            </div>

                            <div class="lp-window-body">
                                <aside class="lp-window-side">
                                    <span class="lp-side-logo"><i class="bi bi-grid-1x2-fill"></i></span>
                                    <i class="bi bi-speedometer2"></i>
                                    <i class="bi bi-hdd-stack active"></i>
                                    <i class="bi bi-kanban"></i>
                                    <i class="bi bi-cash-stack"></i>
                                </aside>

                                <div class="lp-window-main">
                                    <div class="lp-window-head">
                                        <div>
                                            <div class="lp-skeleton-title">Projects</div>
                                            <div class="lp-skeleton-sub">
                                                {{ $stats['active'] }} active {{ Str::plural('system', $stats['active']) }}
                                            </div>
                                        </div>
                                        <span class="lp-mini-pill">Portfolio live</span>
                                    </div>

                                    <div class="lp-mini-grid">
                                        @forelse($consoleSystems as $system)
                                            <div class="lp-mini-card">
                                                <span class="lp-mini-icon {{ $miniIconColors[$loop->index % 4] }}">
                                                    <i class="bi {{ $system->icon ?: 'bi-hdd-stack-fill' }}"></i>
                                                </span>
                                                <div class="lp-mini-card-text">
                                                    <strong>{{ Str::limit($system->name, 16) }}</strong>
                                                    <small>{{ $system->stackLabel() }} · {{ $system->environmentLabel() }}</small>
                                                </div>
                                                <span class="lp-mini-status {{ $system->status === 'active' ? '' : 'warn' }}"></span>
                                            </div>
                                        @empty
                                            @foreach([
                                                ['name' => 'Commerce Suite', 'stack' => 'Laravel', 'env' => 'Production'],
                                                ['name' => 'School Portal', 'stack' => '.NET', 'env' => 'Production'],
                                                ['name' => 'Ops Tracker', 'stack' => 'Node.js', 'env' => 'Staging'],
                                                ['name' => 'Finance Desk', 'stack' => 'Python', 'env' => 'Production'],
                                            ] as $placeholder)
                                                <div class="lp-mini-card">
                                                    <span class="lp-mini-icon {{ $miniIconColors[$loop->index % 4] }}">
                                                        <i class="bi bi-hdd-stack-fill"></i>
                                                    </span>
                                                    <div class="lp-mini-card-text">
                                                        <strong>{{ $placeholder['name'] }}</strong>
                                                        <small>{{ $placeholder['stack'] }} · {{ $placeholder['env'] }}</small>
                                                    </div>
                                                    <span class="lp-mini-status"></span>
                                                </div>
                                            @endforeach
                                        @endforelse
                                    </div>

                                    <div class="lp-window-chart">
                                        <div class="lp-chart-head">
                                            <span>Delivery velocity</span>
                                            <small>Last 8 weeks</small>
                                        </div>
                                        <div class="lp-bars" aria-hidden="true">
                                            <span style="--h:38%"></span>
                                            <span style="--h:56%"></span>
                                            <span style="--h:44%"></span>
                                            <span style="--h:72%"></span>
                                            <span style="--h:61%"></span>
                                            <span style="--h:84%"></span>
                                            <span style="--h:69%"></span>
                                            <span style="--h:94%"></span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="lp-float lp-float-a">
                            <i class="bi bi-check2-circle"></i>
                            <div>
                                <strong>Build passed</strong>
                                <small>main · verified</small>
                            </div>
                        </div>

                        <div class="lp-float lp-float-b">
                            <i class="bi bi-shield-check"></i>
                            <div>
                                <strong>{{ $stats['active'] }} {{ Str::plural('system', $stats['active']) }} tracked</strong>
                                <small>Portfolio in sync</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>

    {{-- Technology strip --}}
    <section class="lp-stack-strip" aria-label="Technologies">
        <div class="container">
            <div class="lp-stack-strip-inner">
                <span class="lp-stack-label">Built with tools we trust</span>
                <div class="lp-stack-row">
                    @foreach(\App\Models\System::STACKS as $key => $label)
                        @continue($key === 'other')
                        <span class="lp-stack-chip">
                            <i class="bi {{ $stackIcons[$key] ?? 'bi-code-slash' }}"></i>
                            {{ $label }}
                        </span>
                    @endforeach
                </div>
            </div>
        </div>
    </section>

    {{-- Projects --}}
    <section id="projects" class="lp-section lp-section-soft">
        <div class="container">
            <div class="lp-section-head lp-reveal">
                <span class="lp-kicker">Portfolio</span>
                <div class="row align-items-end g-3">
                    <div class="col-lg-8">
                        <h2>Systems we build &amp; keep running</h2>
                        <p>
                            A live look at the products currently in our care — planned, built, shipped,
                            and maintained end to end by the FS Tech team.
                        </p>
                    </div>
                    <div class="col-lg-4 text-lg-end">
                        <a href="#contact" class="btn lp-btn-subtle">
                            Request access
                            <i class="bi bi-arrow-right"></i>
                        </a>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                @forelse($featuredSystems as $system)
                    <div class="col-md-6 col-xl-4">
                        <article class="lp-project-card lp-reveal {{ ['', 'lp-delay-1', 'lp-delay-2'][$loop->index % 3] }}">
                            <div class="lp-project-top">
                                <span class="lp-project-icon">
                                    <i class="bi {{ $system->icon ?: 'bi-hdd-stack-fill' }}"></i>
                                </span>
                                <x-status-pill :status="$system->status" />
                            </div>

                            <h3>{{ $system->name }}</h3>
                            <p>{{ Str::limit($system->description ?: 'A production system planned, delivered, and maintained by FS Tech.', 130) }}</p>

                            <div class="lp-project-meta">
                                <span class="lp-chip">
                                    <i class="bi {{ $stackIcons[$system->stack] ?? 'bi-code-slash' }}"></i>
                                    {{ $system->stackLabel() }}
                                </span>
                                <span class="lp-chip">
                                    <i class="bi bi-hdd-network"></i>
                                    {{ $system->environmentLabel() }}
                                </span>
                            </div>

                            <div class="lp-project-foot">
                                <span>
                                    <i class="bi bi-tag"></i>
                                    @if($system->latestVersion)
                                        v{{ ltrim($system->latestVersion->version, 'vV') }}
                                    @else
                                        No release yet
                                    @endif
                                </span>
                                <span class="lp-updated">Updated {{ $system->updated_at->diffForHumans() }}</span>
                            </div>
                        </article>
                    </div>
                @empty
                    <div class="col-12">
                        <div class="lp-empty lp-reveal">
                            <i class="bi bi-box-seam"></i>
                            <h3>Projects are being prepared</h3>
                            <p>New systems will appear here as soon as they go live. Get in touch to hear what we're building next.</p>
                            <a href="#contact" class="btn lp-btn-primary">Talk to us</a>
                        </div>
                    </div>
                @endforelse
            </div>
        </div>
    </section>

    {{-- Capabilities --}}
    <section id="capabilities" class="lp-section">
        <div class="container">
            <div class="lp-section-head text-center lp-reveal">
                <span class="lp-kicker">Capabilities</span>
                <h2>One workspace for the entire lifecycle</h2>
                <p>
                    Replace scattered spreadsheets and chat threads with a single source of truth
                    for delivery, operations, and client billing.
                </p>
            </div>

            <div class="row g-4">
                @foreach($capabilities as $feature)
                    <div class="col-md-6 col-xl-4">
                        <div class="lp-feature lp-reveal {{ ['', 'lp-delay-1', 'lp-delay-2'][$loop->index % 3] }}">
                            <span class="lp-feature-icon">
                                <i class="bi {{ $feature['icon'] }}"></i>
                            </span>
                            <h3>{{ $feature['title'] }}</h3>
                            <p>{{ $feature['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Workflow --}}
    <section id="workflow" class="lp-section lp-section-dark">
        <div class="container">
            <div class="lp-section-head text-center lp-reveal">
                <span class="lp-kicker light">How we work</span>
                <h2>A clear path from idea to maintenance</h2>
                <p>Every project follows the same transparent rhythm, so nothing gets lost between phases.</p>
            </div>

            <div class="row g-4">
                @foreach($workflow as $step)
                    <div class="col-md-6 col-xl-3">
                        <div class="lp-step lp-reveal {{ ['', 'lp-delay-1', 'lp-delay-2'][$loop->index % 3] }}">
                            <span class="lp-step-num">0{{ $loop->iteration }}</span>
                            <span class="lp-step-icon">
                                <i class="bi {{ $step['icon'] }}"></i>
                            </span>
                            <h3>{{ $step['title'] }}</h3>
                            <p>{{ $step['text'] }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    </section>

    {{-- Contact / CTA --}}
    <section id="contact" class="lp-section-cta">
        <div class="container">
            <div class="lp-cta lp-reveal">
                <div class="row align-items-center g-4">
                    <div class="col-lg-7">
                        <span class="lp-kicker light">Let's build</span>
                        <h2>Have a system in mind?</h2>
                        <p>
                            Tell us what you need. We'll scope it, build it, and keep it running —
                            with every milestone and release visible along the way.
                        </p>
                        <div class="d-flex flex-wrap gap-3">
                            <a href="mailto:mathfarors@gmail.com?subject=Project%20inquiry" class="btn lp-btn-white btn-lg">
                                <i class="bi bi-envelope-fill"></i>
                                mathfarors@gmail.com
                            </a>
                            <a href="https://t.me/farors07" target="_blank" rel="noopener" class="btn lp-btn-ghost-light btn-lg">
                                <i class="bi bi-telegram"></i>
                                Telegram
                            </a>
                            <a href="tel:0967444964" class="btn lp-btn-ghost-light btn-lg">
                                <i class="bi bi-telephone-fill"></i>
                                0967444964
                            </a>
                        </div>
                    </div>

                    <div class="col-lg-5">
                        <div class="lp-contact-card">
                            <div class="lp-contact-row">
                                <i class="bi bi-telephone-fill"></i>
                                <div>
                                    <strong>Direct Phone</strong>
                                    <a href="tel:0967444964">0967444964</a>
                                </div>
                            </div>
                            <div class="lp-contact-row">
                                <i class="bi bi-facebook"></i>
                                <div>
                                    <strong>Facebook Page</strong>
                                    <span>FS Tech Systems</span>
                                </div>
                            </div>
                            <div class="lp-contact-row">
                                <i class="bi bi-telegram"></i>
                                <div>
                                    <strong>Telegram</strong>
                                    <a href="https://t.me/farors07" target="_blank" rel="noopener">@farors07</a>
                                </div>
                            </div>
                            <div class="lp-contact-row">
                                <i class="bi bi-envelope-at-fill"></i>
                                <div>
                                    <strong>Email</strong>
                                    <a href="mailto:mathfarors@gmail.com">mathfarors@gmail.com</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
</main>

{{-- Footer --}}
<footer class="lp-footer">
    <div class="container">
        <div class="row g-4">
            <div class="col-lg-5">
                <a class="lp-brand" href="{{ route('welcome') }}">
                    <x-brand-logo variant="mark" height="38" />
                    <span class="lp-brand-text">
                        <strong>FS Tech</strong>
                        <small>System Manager</small>
                    </span>
                </a>
                <p class="lp-footer-blurb">
                    The internal command center behind the systems we build — projects, releases,
                    tasks, operations, and finances in one place.
                </p>
                <div class="d-flex flex-column gap-2 mt-3">
                    <a class="lp-footer-mail" href="tel:0967444964">
                        <i class="bi bi-telephone-fill"></i>
                        0967444964
                    </a>
                    <a class="lp-footer-mail" href="mailto:mathfarors@gmail.com">
                        <i class="bi bi-envelope-fill"></i>
                        mathfarors@gmail.com
                    </a>
                    <a class="lp-footer-mail" href="https://t.me/farors07" target="_blank" rel="noopener">
                        <i class="bi bi-telegram"></i>
                        Telegram: @farors07
                    </a>
                    <div class="lp-footer-mail" style="color: rgba(255,255,255,0.7);">
                        <i class="bi bi-facebook"></i>
                        Facebook: FS Tech Systems
                    </div>
                </div>
            </div>

            <div class="col-6 col-lg-2">
                <h4>Explore</h4>
                <ul class="lp-footer-links">
                    <li><a href="#projects">Projects</a></li>
                    <li><a href="#capabilities">Capabilities</a></li>
                    <li><a href="#workflow">How we work</a></li>
                    <li><a href="#contact">Contact</a></li>
                </ul>
            </div>

            <div class="col-6 col-lg-2">
                <h4>Platform</h4>
                <ul class="lp-footer-links">
                    <li><a href="{{ route('login') }}">Admin sign in</a></li>
                    <li><a href="mailto:admin@fstech.com?subject=Project%20inquiry">Request a quote</a></li>
                    <li><a href="mailto:admin@fstech.com?subject=Walkthrough%20request">Book a walkthrough</a></li>
                </ul>
            </div>

            <div class="col-lg-3">
                <h4>Status</h4>
                <p class="lp-footer-blurb mb-2">
                    {{ $stats['active'] }} active {{ Str::plural('system', $stats['active']) }}
                    across {{ $stats['stacks'] }} {{ Str::plural('stack', $stats['stacks']) }},
                    with {{ $stats['releases'] }} {{ Str::plural('release', $stats['releases']) }} tracked.
                </p>
                <a class="lp-footer-mail" href="#projects">
                    <i class="bi bi-arrow-up-right"></i>
                    View the portfolio
                </a>
            </div>
        </div>

        <div class="lp-footer-bottom">
            <span>&copy; {{ now()->year }} FS Tech. All rights reserved.</span>
            <span>{{ config('app.name') }} · Built for clarity.</span>
        </div>
    </div>
</footer>
@endsection

@push('scripts')
<script>
    document.documentElement.classList.add('js');

    (() => {
        const nav = document.getElementById('lpNav');
        const onScroll = () => nav.classList.toggle('is-scrolled', window.scrollY > 24);
        onScroll();
        window.addEventListener('scroll', onScroll, { passive: true });

        const menu = document.getElementById('lpNavMenu');
        const mobile = window.matchMedia('(max-width: 991.98px)');

        if (menu) {
            menu.querySelectorAll('a[href^="#"]').forEach((link) => {
                link.addEventListener('click', () => {
                    if (!mobile.matches || !menu.classList.contains('show')) {
                        return;
                    }

                    bootstrap.Collapse.getOrCreateInstance(menu, { toggle: false }).hide();
                });
            });
        }

        const items = document.querySelectorAll('.lp-reveal');
        const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        if (!('IntersectionObserver' in window) || reducedMotion) {
            items.forEach((item) => item.classList.add('is-visible'));
            return;
        }

        const observer = new IntersectionObserver((entries) => {
            entries.forEach((entry) => {
                if (!entry.isIntersecting) {
                    return;
                }

                entry.target.classList.add('is-visible');
                observer.unobserve(entry.target);
            });
        }, { threshold: 0.15, rootMargin: '0px 0px -40px 0px' });

        items.forEach((item) => observer.observe(item));
    })();
</script>
@endpush
