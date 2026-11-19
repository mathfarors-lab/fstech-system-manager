<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="#0c0f1e">
    <title>Sign in · {{ config('app.name') }}</title>
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
</head>
<body class="fst-auth-body">
<main class="fst-auth">
    <section class="fst-auth-showcase" aria-hidden="true">
        <div class="fst-auth-brand">
            <span class="fst-brand-mark fst-brand-mark-lg" aria-hidden="true">FS</span>
            <span class="fst-brand-text">
                <strong>FS Tech</strong>
                <small>System Manager</small>
            </span>
        </div>

        <div class="fst-auth-copy">
            <span class="fst-auth-eyebrow"><i class="bi bi-stars"></i>Developer workspace</span>
            <h2 class="fst-auth-headline">Every project, release and payment in one clear view.</h2>
            <p class="fst-auth-lead">Keep your systems, task board, deployment history and finance records in sync — so your team can focus on shipping.</p>

            <div class="fst-auth-features">
                <div class="fst-auth-feature">
                    <span class="fst-auth-feature-icon"><i class="bi bi-hdd-stack"></i></span>
                    <div>
                        <strong>Systems &amp; releases</strong>
                        <span>Track environments, versions and builds across every product.</span>
                    </div>
                </div>
                <div class="fst-auth-feature">
                    <span class="fst-auth-feature-icon"><i class="bi bi-kanban"></i></span>
                    <div>
                        <strong>Task board</strong>
                        <span>Move work through a clear kanban flow with priorities and notes.</span>
                    </div>
                </div>
                <div class="fst-auth-feature">
                    <span class="fst-auth-feature-icon"><i class="bi bi-cash-coin"></i></span>
                    <div>
                        <strong>Finance tracking</strong>
                        <span>Monitor transactions and payment stages per project.</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="fst-auth-showcase-foot">
            <i class="bi bi-shield-check"></i>
            <span>Secure internal workspace · FS Tech Systems</span>
        </div>
    </section>

    <section class="fst-auth-form-wrap">
        <div class="fst-auth-card">
            <div class="fst-auth-mobile-brand">
                <span class="fst-brand-mark" aria-hidden="true">FS</span>
                <span class="fst-brand-text">
                    <strong>FS Tech</strong>
                    <small>System Manager</small>
                </span>
            </div>

            <p class="fst-auth-card-kicker">Welcome back</p>
            <h1 class="fst-auth-card-title">Sign in</h1>
            <p class="fst-auth-card-sub">Enter your credentials to open the workspace.</p>

            @if($errors->any())
                <div class="fst-auth-alert" role="alert">
                    <i class="bi bi-exclamation-circle-fill"></i>
                    <span>{{ $errors->first() }}</span>
                </div>
            @endif

            <form class="fst-auth-form" method="POST" action="{{ route('login.attempt') }}">
                @csrf

                <div class="fst-auth-field">
                    <label class="form-label" for="email">Email address</label>
                    <div class="fst-auth-input">
                        <i class="bi bi-envelope"></i>
                        <input type="email" id="email" name="email" value="{{ old('email') }}"
                               class="form-control @error('email') is-invalid @enderror"
                               placeholder="you@fstech.com" required autofocus autocomplete="username">
                    </div>
                </div>

                <div class="fst-auth-field">
                    <label class="form-label" for="password">Password</label>
                    <div class="fst-auth-input">
                        <i class="bi bi-lock"></i>
                        <input type="password" id="password" name="password"
                               class="form-control @error('password') is-invalid @enderror"
                               placeholder="Enter your password" required autocomplete="current-password">
                        <button type="button" class="fst-auth-eye" id="togglePassword" aria-label="Show password" aria-pressed="false">
                            <i class="bi bi-eye"></i>
                        </button>
                    </div>
                </div>

                <div class="fst-auth-meta">
                    <div class="form-check mb-0">
                        <input class="form-check-input" type="checkbox" name="remember" id="remember">
                        <label class="form-check-label" for="remember">Remember me</label>
                    </div>
                    <span class="fst-auth-hint">Need access? Contact an admin</span>
                </div>

                <button type="submit" class="btn btn-fst-primary fst-auth-submit">
                    <i class="bi bi-box-arrow-in-right"></i>Sign in
                </button>
            </form>

            <p class="fst-auth-foot">
                <i class="bi bi-shield-lock"></i>&copy; {{ date('Y') }} FS Tech Systems
            </p>
        </div>
    </section>
</main>

<script>
    (function () {
        var input = document.getElementById('password');
        var toggle = document.getElementById('togglePassword');

        toggle.addEventListener('click', function () {
            var show = input.type === 'password';
            input.type = show ? 'text' : 'password';
            toggle.querySelector('i').className = 'bi ' + (show ? 'bi-eye-slash' : 'bi-eye');
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', show ? 'true' : 'false');
        });
    })();
</script>
</body>
</html>
