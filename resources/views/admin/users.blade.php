@extends('layouts.admin')
@section('title', 'Manage Users')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <h2 class="mb-0">Users</h2>
    <a href="{{ route('admin.users.create') }}" class="btn btn-admin btn-admin-primary">+ Add user</a>
</div>

<form method="GET" class="row g-2 mb-4">
    <div class="col-auto">
        <input type="text" name="search" class="form-control" placeholder="Search name, email or username..." value="{{ request('search') }}" style="min-width: 260px;">
    </div>
    <div class="col-auto">
        <select name="role" class="form-select">
            <option value="">All roles</option>
            <option value="client" @selected(request('role') == 'client')>Client</option>
            <option value="partner" @selected(request('role') == 'partner')>Partner</option>
            <option value="admin" @selected(request('role') == 'admin')>Admin</option>
        </select>
    </div>
    <div class="col-auto">
        <button type="submit" class="btn btn-admin btn-admin-primary">Search</button>
        @if(request('search') || request('role'))
            <a href="{{ route('admin.users') }}" class="btn btn-admin btn-admin-outline">Clear</a>
        @endif
    </div>
</form>

<div class="table-responsive">
<table class="table table-hover">
    <thead><tr><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th class="text-end">Actions</th></tr></thead>
    <tbody>
        @forelse($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td><span class="badge bg-secondary">{{ ucfirst($user->role) }}</span></td>
                <td>
                    @if($user->isBanned())
                        <span class="badge bg-dark">Banned</span>
                    @elseif($user->is_suspended)
                        <span class="badge bg-danger">Suspended</span>
                    @else
                        <span class="badge bg-success">Active</span>
                    @endif
                </td>
                <td>
                    <div class="fx-row-actions justify-content-end">
                        <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-admin btn-admin-outline">Edit</a>
                        @unless($user->isAdmin() || $user->isBanned())
                            <div class="fx-row-menu" x-data="{ open: false }" @click.outside="open = false">
                                <button type="button" class="btn btn-admin btn-admin-ghost" @click="open = !open" aria-label="More actions">&hellip;</button>
                                <div class="fx-row-menu-panel" x-show="open" x-cloak x-transition.duration.120ms>
                                    @if($user->is_suspended)
                                        <form method="POST" action="{{ route('admin.users.unsuspend', $user) }}">
                                            @csrf
                                            <button type="submit" class="fx-row-menu-item">Reinstate</button>
                                        </form>
                                    @else
                                        <form method="POST" action="{{ route('admin.users.suspend', $user) }}">
                                            @csrf
                                            <button type="submit" class="fx-row-menu-item">Suspend</button>
                                        </form>
                                    @endif
                                    <div class="fx-row-menu-divider"></div>
                                    <form method="POST" action="{{ route('admin.users.ban', $user) }}" onsubmit="return confirm('Permanently ban {{ $user->name }}? This cannot be undone: they will never be able to log in again, and their email/phone will be blocked from re-registering. Their existing reservations, payments and contracts stay intact.')">
                                        @csrf
                                        <button type="submit" class="fx-row-menu-item fx-row-menu-item-danger">Ban permanently</button>
                                    </form>
                                    <form method="POST" action="{{ route('admin.users.destroy', $user) }}" onsubmit="return confirm('Delete this user? This also deletes their reservations and, if a partner, their vehicles.')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="fx-row-menu-item fx-row-menu-item-danger">Delete</button>
                                    </form>
                                </div>
                            </div>
                        @endunless
                    </div>
                </td>
            </tr>
        @empty
            <tr><td colspan="5" class="text-muted">No users found.</td></tr>
        @endforelse
    </tbody>
</table>
</div>
{{ $users->links() }}
@endsection
