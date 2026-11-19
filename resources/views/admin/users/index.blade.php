@extends('layouts.app')

@section('title', 'Users')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Users</h4>
        <small class="text-body-secondary">{{ $users->total() }} team members</small>
    </div>
    <a href="{{ route('users.create') }}" class="btn btn-fst-primary btn-sm"><i class="bi bi-person-plus me-1"></i>New User</a>
</div>

<div class="fst-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table fst-table mb-0">
            <thead><tr><th>User</th><th>Role</th><th>Open tasks</th><th>Joined</th><th class="text-end">Actions</th></tr></thead>
            <tbody>
            @foreach($users as $user)
                <tr class="{{ $user->id === auth()->id() ? 'fst-highlight' : '' }}">
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="avatar avatar-sm" style="background:linear-gradient(135deg,#6366f1,#a855f7)">{{ strtoupper(substr($user->name, 0, 1)) }}</span>
                            <div>
                                <div class="fw-semibold">{{ $user->name }} @if($user->id === auth()->id())<small class="text-body-secondary">(you)</small>@endif</div>
                                <small class="text-body-secondary">{{ $user->email }}</small>
                            </div>
                        </div>
                    </td>
                    <td>
                        @php $roleColor = ['admin' => 'danger', 'developer' => 'primary', 'viewer' => 'secondary'][$user->role]; @endphp
                        <span class="fst-pill pill-{{ $roleColor }}">{{ $user->roleLabel() }}</span>
                    </td>
                    <td><small>{{ $user->open_tasks_count }}</small></td>
                    <td><small>{{ $user->created_at->format('M j, Y') }}</small></td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-light border"><i class="bi bi-pencil"></i></a>
                        @if($user->id !== auth()->id())
                            <form method="POST" action="{{ route('users.destroy', $user) }}" class="d-inline" onsubmit="fstConfirm(event, 'Delete user \'{{ $user->name }}\'?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="mt-3 d-flex justify-content-end">{{ $users->links() }}</div>
@endsection
