@extends('layouts.app')

@section('title', 'Finance')

@section('content')
<div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-2">
    <div>
        <h4 class="fw-bold mb-0">Finance</h4>
        <small class="text-body-secondary">Income, expenses and profitability across your systems</small>
    </div>
    <div class="d-flex gap-2 align-items-center">
        <form method="GET" action="{{ route('finance.dashboard') }}">
            <select name="year" class="form-select form-select-sm" onchange="this.form.submit()">
                @foreach(range(now()->year - 3, now()->year) as $y)
                    <option value="{{ $y }}" {{ $year === $y ? 'selected' : '' }}>{{ $y }}</option>
                @endforeach
            </select>
        </form>
        <a href="{{ route('finance.transactions') }}" class="btn btn-light border btn-sm"><i class="bi bi-list-ul me-1"></i>Ledger</a>
        @if(auth()->user()->canWrite())
            <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#txModal"><i class="bi bi-plus-lg me-1"></i>Transaction</button>
        @endif
    </div>
</div>

{{-- Summary cards per currency --}}
<div class="row g-4 mb-5">
    @foreach($summary as $item)
        <div class="col-md-4">
            <div class="fst-card p-4 border-0 shadow-sm" style="border-radius: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <small class="text-uppercase fw-bold text-muted" style="letter-spacing: 0.05em; font-size: 0.75rem;">
                        {{ $item['currency'] }} Balance · {{ $year }}
                    </small>
                    <span class="badge {{ $item['net'] >= 0 ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }} rounded-pill px-2">
                        {{ $item['net'] >= 0 ? 'Profit' : 'Loss' }}
                    </span>
                </div>
                <div class="display-6 fw-bold mb-3">
                    {{ $item['currency'] === 'KHR' ? '៛' : '$' }}{{ number_format(abs($item['net']), $item['currency'] === 'KHR' ? 0 : 2) }}
                </div>
                <div class="d-flex justify-content-between text-muted small">
                    <span>Income: {{ $item['currency'] === 'KHR' ? '៛' : '$' }}{{ number_format($item['income'], $item['currency'] === 'KHR' ? 0 : 2) }}</span>
                    <span>Expenses: {{ $item['currency'] === 'KHR' ? '៛' : '$' }}{{ number_format($item['expense'], $item['currency'] === 'KHR' ? 0 : 2) }}</span>
                </div>
            </div>
        </div>
    @endforeach
    @if($summary->isEmpty())
        <div class="col-12">
            <div class="fst-card text-center py-4 text-body-secondary">
                <i class="bi bi-cash-stack" style="font-size:2rem"></i>
                <p class="small mt-2 mb-2">No transactions recorded for {{ $year }}</p>
                @if(auth()->user()->canWrite())
                    <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#txModal">Record first transaction</button>
                @endif
            </div>
        </div>
    @endif
</div>

<div class="row g-3">
    {{-- Monthly chart per currency --}}
    @foreach($chartPerCurrency as $idx => $chart)
        <div class="col-lg-{{ $loop->last && $idx === 0 ? 12 : ($chartPerCurrency->count() > 1 ? 6 : 12) }}">
            <x-card title="Monthly Trend — {{ $chart['currency'] }}" subtitle="Income vs expense, last 12 months">
                <canvas id="trendChart{{ $loop->index }}" height="110"></canvas>
            </x-card>
        </div>
    @endforeach

    {{-- By system --}}
    <div class="col-lg-7">
        <x-card title="Profitability by System" :subtitle="$mainCurrency.' · '.$year">
            <div class="table-responsive">
                <table class="table fst-table mb-0">
                    <thead><tr><th>System</th><th class="text-end">Income</th><th class="text-end">Expense</th><th class="text-end">Net</th></tr></thead>
                    <tbody>
                    @forelse($systemBreakdown as $row)
                        <tr>
                            <td>
                                @if($row['name'] !== 'General')
                                    <a class="link-plain fw-semibold small" href="{{ route('systems.index', ['q' => $row['name']]) }}">{{ $row['name'] }}</a>
                                @else
                                    <span class="small text-body-secondary fst-italic">General (no system)</span>
                                @endif
                            </td>
                            <td class="text-end small">{{ number_format($row['income'], 2) }}</td>
                            <td class="text-end small">{{ number_format($row['expense'], 2) }}</td>
                            <td class="text-end small fw-semibold {{ $row['net'] >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format($row['net'], 2) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center py-4 text-body-secondary">No data</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>

    {{-- By category --}}
    <div class="col-lg-5">
        <x-card title="By Category" :subtitle="$mainCurrency.' · '.$year">
            <canvas id="categoryChart" height="220"></canvas>
        </x-card>
    </div>

    {{-- Recent transactions --}}
    <div class="col-12">
        <x-card title="Recent Transactions">
            <x-slot:actions><a class="btn btn-sm btn-light border" href="{{ route('finance.transactions') }}">View all</a></x-slot:actions>
            <div class="table-responsive">
                <table class="table fst-table mb-0">
                    <thead><tr><th>Date</th><th>Title</th><th>System</th><th>Category</th><th class="text-end">Amount</th></tr></thead>
                    <tbody>
                    @forelse($recent as $tx)
                        <tr>
                            <td><small>{{ $tx->transaction_date->format('M j, Y') }}</small></td>
                            <td>
                                <span class="small fw-semibold">{{ $tx->title }}</span>
                                <x-status-pill status="{{ $tx->type }}" />
                            </td>
                            <td><small>{{ $tx->system?->name ?? '—' }}</small></td>
                            <td><small>{{ $tx->categoryLabel() }}</small></td>
                            <td class="text-end fw-semibold small {{ $tx->type === 'income' ? 'text-success' : 'text-danger' }}">
                                {{ $tx->formatAmount() }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="text-center py-4 text-body-secondary">No transactions</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </x-card>
    </div>
</div>

@if(auth()->user()->canWrite())
<div class="modal fade" id="txModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Record Transaction</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">
                @include('admin.finance._form')
            </div>
        </div>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script>
    const palette = ['#16a34a', '#dc2626', '#6366f1', '#a855f7', '#06b6d4', '#f59e0b', '#8b5cf6', '#ef4444', '#22c55e', '#0ea5e9', '#f97316', '#64748b'];

    @foreach($chartPerCurrency as $chart)
    new Chart(document.getElementById('trendChart{{ $loop->index }}'), {
        type: 'line',
        data: {
            labels: @json($chart['labels']->map(fn ($ym) => \Carbon\Carbon::parse($ym.'-01')->format('M y'))),
            datasets: [
                { label: 'Income', data: @json($chart['income']), borderColor: '#16a34a', backgroundColor: 'rgba(22,163,74,.1)', fill: true, tension: .35 },
                { label: 'Expense', data: @json($chart['expense']), borderColor: '#dc2626', backgroundColor: 'rgba(220,38,38,.1)', fill: true, tension: .35 },
            ]
        },
        options: { plugins: { legend: { position: 'bottom' } }, scales: { y: { beginAtZero: true } } }
    });
    @endforeach

    @if($categoryBreakdown->isNotEmpty())
    new Chart(document.getElementById('categoryChart'), {
        type: 'bar',
        data: {
            labels: @json($categoryBreakdown->map(fn ($c) => \App\Models\FinanceTransaction::CATEGORIES[$c->category] ?? $c->category)),
            datasets: [
                @php
                    $categoryColors = $categoryBreakdown->map(fn ($c) => $c->type === 'income' ? 'rgba(22,163,74,.75)' : 'rgba(220,38,38,.75)');
                @endphp
                {
                data: @json($categoryBreakdown->pluck('total')),
                backgroundColor: @json($categoryColors),
                borderRadius: 6,
                maxBarThickness: 26,
            }]
        },
        options: {
            indexAxis: 'y',
            plugins: { legend: { display: false } },
            scales: { x: { beginAtZero: true } }
        }
    });
    @endif
</script>
@endpush
