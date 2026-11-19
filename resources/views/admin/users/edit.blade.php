@extends('layouts.app')

@section('title', 'Edit User')

@section('content')
<div class="mb-4">
    <a href="{{ route('users.index') }}" class="link-plain small"><i class="bi bi-arrow-left me-1"></i>Back to users</a>
    <h4 class="fw-bold mb-0 mt-1">Edit User — {{ $user->name }}</h4>
</div>

<div class="fst-card p-4" style="max-width:640px">
    <form method="POST" action="{{ route('users.update', $user) }}" class="row g-3">
        @csrf @method('PUT')
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Name</label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Email</label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Role</label>
            <select name="role" class="form-select">
                <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin — full access</option>
                <option value="developer" {{ $user->role === 'developer' ? 'selected' : '' }}>Developer — manage systems, tasks, builds</option>
                <option value="viewer" {{ $user->role === 'viewer' ? 'selected' : '' }}>Viewer — read only</option>
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">New Password <small class="text-body-secondary">(leave blank to keep)</small></label>
            <input type="password" name="password" class="form-control">
        </div>
        <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
            <a href="{{ route('users.index') }}" class="btn btn-light border">Cancel</a>
            <button class="btn btn-fst-primary px-4">Save Changes</button>
        </div>
    </form>
</div>
@endsection
