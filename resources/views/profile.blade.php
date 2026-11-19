@extends('layouts.app')

@section('title', 'Profile')

@section('content')
<div class="mb-4"><h4 class="fw-bold mb-0">Profile</h4></div>

<div class="row g-3">
    <div class="col-md-6">
        <div class="fst-card p-4">
            <h5 class="fw-bold mb-3">Account</h5>
            <form method="POST" action="{{ route('profile.update') }}" class="row g-3">
                @csrf
                @method('PATCH')
                <div class="col-12">
                    <label class="form-label small fw-semibold">Name</label>
                    <input type="text" name="name" class="form-control" value="{{ $user->name }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label small fw-semibold">Email</label>
                    <input type="email" name="email" class="form-control" value="{{ $user->email }}" required>
                </div>
                <div class="col-12 d-flex justify-content-end border-top pt-3">
                    <button class="btn btn-fst-primary">Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-md-6">
        <div class="fst-card p-4">
            <h5 class="fw-bold mb-3">Password</h5>
            <form method="POST" action="{{ route('password.update') }}" class="row g-3">
                @csrf
                @method('PUT')
                <div class="col-12">
                    <label class="form-label small fw-semibold">Current password</label>
                    <input type="password" name="current_password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">New password</label>
                    <input type="password" name="password" class="form-control" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label small fw-semibold">Confirm new password</label>
                    <input type="password" name="password_confirmation" class="form-control" required>
                </div>
                <div class="col-12 d-flex justify-content-end border-top pt-3">
                    <button class="btn btn-fst-primary">Update Password</button>
                </div>
            </form>
        </div>

        <div class="fst-card p-4 mt-3">
            <div class="d-flex align-items-center gap-3">
                <span class="system-avatar" style="background:linear-gradient(135deg,#6366f1,#a855f7)">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                <div>
                    <div class="fw-bold">{{ $user->name }}</div>
                    <span class="fst-pill pill-{{ ['admin' => 'danger', 'developer' => 'primary', 'viewer' => 'secondary'][$user->role] }}">{{ $user->roleLabel() }}</span>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
