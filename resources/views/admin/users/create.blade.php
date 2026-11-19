@extends('layouts.app')

@section('title', 'New User')

@section('content')
<div class="mb-4">
    <a href="{{ route('users.index') }}" class="link-plain small"><i class="bi bi-arrow-left me-1"></i>Back to users</a>
    <h4 class="fw-bold mb-0 mt-1">New User</h4>
</div>

<div class="fst-card p-4" style="max-width:640px">
    <form method="POST" action="{{ route('users.store') }}" class="row g-3">
        @csrf
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name') }}" required>
            @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Email <span class="text-danger">*</span></label>
            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email') }}" required>
            @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Password <span class="text-danger">*</span></label>
            <input type="password" name="password" class="form-control" required>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Role</label>
            <select name="role" class="form-select">
                <option value="admin">Admin — full access</option>
                <option value="developer" selected>Developer — manage systems, tasks, builds</option>
                <option value="viewer">Viewer — read only</option>
            </select>
        </div>
        <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
            <a href="{{ route('users.index') }}" class="btn btn-light border">Cancel</a>
            <button class="btn btn-fst-primary px-4">Create User</button>
        </div>
    </form>
</div>
@endsection
