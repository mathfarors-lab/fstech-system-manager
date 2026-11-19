@extends('layouts.app')

@section('title', 'Finance Ledger')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4">
    <div>
        <h4 class="fw-bold mb-0">Finance Ledger</h4>
        <small class="text-body-secondary">{{ $transactions->total() }} transactions</small>
    </div>
    @if(auth()->user()->canWrite())
        <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#txModal"><i class="bi bi-plus-lg me-1"></i>Record Transaction</button>
    @endif
</div>

<div class="fst-card p-3 mb-3">
    <form method="GET" action="{{ route('finance.transactions') }}" class="d-flex flex-wrap gap-2 align-items-center">
        <div class="input-group input-group-sm" style="width:220px">
            <span class="input-group-text bg-transparent border-end-0"><i class="bi bi-search"></i></span>
            <input type="search" name="q" class="form-control border-start-0 ps-0" placeholder="Search title..." value="{{ $filters['q'] ?? '' }}">
        </div>
        <select name="system" class="form-select form-select-sm" style="width:auto">
            <option value="">All systems</option>
            @foreach($systems as $s)
                <option value="{{ $s->id }}" {{ ($filters['system'] ?? null) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
            @endforeach
        </select>
        <select name="type" class="form-select form-select-sm" style="width:auto">
            <option value="">Income & Expense</option>
            @foreach(\App\Models\FinanceTransaction::TYPES as $k => $l)
                <option value="{{ $k }}" {{ ($filters['type'] ?? null) === $k ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="category" class="form-select form-select-sm" style="width:auto">
            <option value="">All categories</option>
            @foreach(\App\Models\FinanceTransaction::CATEGORIES as $k => $l)
                <option value="{{ $k }}" {{ ($filters['category'] ?? null) === $k ? 'selected' : '' }}>{{ $l }}</option>
            @endforeach
        </select>
        <select name="currency" class="form-select form-select-sm" style="width:auto">
            <option value="">All currencies</option>
            <option value="USD" {{ ($filters['currency'] ?? null) === 'USD' ? 'selected' : '' }}>USD</option>
            <option value="KHR" {{ ($filters['currency'] ?? null) === 'KHR' ? 'selected' : '' }}>KHR</option>
        </select>
        <input type="month" name="month" class="form-control form-control-sm" style="width:auto" value="{{ $filters['month'] ?? '' }}">
        <button class="btn btn-light border btn-sm">Filter</button>
        <a href="{{ route('finance.transactions') }}" class="btn btn-light border btn-sm" title="Clear"><i class="bi bi-x-lg"></i></a>
    </form>
</div>

<div class="fst-card p-0 overflow-hidden">
    <div class="table-responsive">
        <table class="table fst-table mb-0">
            <thead><tr>
                <th>Date</th><th>Title</th><th>System</th><th>Type</th><th>Category</th>
                <th class="text-end">Amount</th><th>Recorded by</th><th class="text-end">Actions</th>
            </tr></thead>
            <tbody>
            @forelse($transactions as $tx)
                <tr>
                    <td><small>{{ $tx->transaction_date->format('M j, Y') }}</small></td>
                    <td>
                        <span class="small fw-semibold">{{ $tx->title }}</span>
                        @if($tx->description)<small class="d-block text-body-secondary">{{ \Illuminate\Support\Str::limit($tx->description, 70) }}</small>@endif
                    </td>
                    <td>
                        @if($tx->system)
                            <a class="link-plain small" href="{{ route('systems.show', $tx->system) }}">{{ $tx->system->name }}</a>
                        @else
                            <small class="text-body-secondary fst-italic">General</small>
                        @endif
                    </td>
                    <td><x-status-pill :status="$tx->type" /></td>
                    <td><small>{{ $tx->categoryLabel() }}</small></td>
                    <td class="text-end fw-semibold small {{ $tx->type === 'income' ? 'text-success' : 'text-danger' }}">{{ $tx->formatAmount() }}</td>
                    <td><small>{{ $tx->createdBy?->name ?? '—' }}</small></td>
                    <td class="text-end">
                        @if(auth()->user()->canWrite())
                            <button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editTxModal{{ $tx->id }}"><i class="bi bi-pencil"></i></button>
                            <form method="POST" action="{{ route('finance.transactions.destroy', $tx) }}" class="d-inline" onsubmit="fstConfirm(event, 'Delete transaction \'{{ $tx->title }}\'?')">@csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="8" class="text-center py-5 text-body-secondary">No transactions found</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

<div class="d-flex justify-content-between align-items-center mt-3">
    <small class="text-body-secondary">Showing {{ $transactions->firstItem() ?? 0 }}–{{ $transactions->lastItem() ?? 0 }} of {{ $transactions->total() }}</small>
    {{ $transactions->appends(request()->query())->links() }}
</div>

@if(auth()->user()->canWrite())
<div class="modal fade" id="txModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Record Transaction</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('admin.finance._form')</div>
        </div>
    </div>
</div>

@foreach($transactions as $tx)
<div class="modal fade" id="editTxModal{{ $tx->id }}" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Edit Transaction</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('admin.finance._form', ['transaction' => $tx])</div>
        </div>
    </div>
</div>
@endforeach
@endif
@endsection
