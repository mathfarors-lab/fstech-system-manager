{{-- Finance tab — per-system income/expense summary --}}
@php
$incomeRows = $summary->get('USD', collect());
$usdIncome = (float) $incomeRows->where('type', 'income')->sum('total');
$usdExpense = (float) $incomeRows->where('type', 'expense')->sum('total');
$usdNet = $usdIncome - $usdExpense;
$khrIncome = (float) $summary->get('KHR', collect())->where('type', 'income')->sum('total');
$khrExpense = (float) $summary->get('KHR', collect())->where('type', 'expense')->sum('total');

$catSum = function (string $category, string $currency) use ($categorySummary) {
    return (float) $categorySummary->get($category, collect())->where('currency', $currency)->sum('total');
};
$usdBuild = $catSum('build_fee', 'USD');
$usdMaint = $catSum('maintenance_fee', 'USD');
$khrBuild = $catSum('build_fee', 'KHR');
$khrMaint = $catSum('maintenance_fee', 'KHR');
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-4"><div class="fst-card p-3 text-center">
        <div class="fs-5 fw-bold text-success">${{ number_format($usdIncome, 2) }}</div>
        <small class="text-body-secondary">Income (USD)</small>
        <small class="d-block text-body-secondary mt-1">Build ${{ number_format($usdBuild, 2) }} · Maint. ${{ number_format($usdMaint, 2) }}</small>
        @if($khrIncome)<small class="d-block text-body-secondary">+ ៛{{ number_format($khrIncome) }} KHR</small>@endif
    </div></div>
    <div class="col-md-4"><div class="fst-card p-3 text-center">
        <div class="fs-5 fw-bold text-danger">${{ number_format($usdExpense, 2) }}</div>
        <small class="text-body-secondary">Expenses (USD)</small>
        @if($khrExpense)<small class="d-block text-body-secondary">+ ៛{{ number_format($khrExpense) }} KHR</small>@endif
    </div></div>
    <div class="col-md-4"><div class="fst-card p-3 text-center">
        <div class="fs-5 fw-bold {{ $usdNet >= 0 ? 'text-success' : 'text-danger' }}">${{ number_format($usdNet, 2) }}</div>
        <small class="text-body-secondary">Net {{ $usdNet >= 0 ? 'profit' : 'loss' }} (USD)</small>
    </div></div>
</div>

@if(auth()->user()->canWrite() && !$system->trashed())
<div class="d-flex justify-content-end gap-2 mb-3">
    <a href="{{ route('systems.invoice.pdf', $system) }}" class="btn btn-light border btn-sm"><i class="bi bi-file-earmark-pdf me-1"></i>Save PDF</a>
    <a href="{{ route('systems.invoice', $system) }}" class="btn btn-light border btn-sm" target="_blank"><i class="bi bi-printer me-1"></i>Print Invoice</a>
    <button class="btn btn-fst-primary btn-sm" data-bs-toggle="modal" data-bs-target="#financeTabModal"><i class="bi bi-plus-lg me-1"></i>Record Transaction</button>
</div>
@endif

@if($system->maintenance_due_date && !$system->maintenance_collected_at)
    <div class="alert {{ $system->maintenance_due_date->isPast() ? 'alert-danger' : 'alert-warning' }} mb-3">
        <div class="d-flex align-items-center justify-content-between">
            <div>
                <strong>Maintenance Due:</strong> {{ $system->maintenance_due_date->format('M j, Y') }}
                @if($system->maintenance_due_date->isPast()) (Overdue) @endif
            </div>
            <form method="POST" action="{{ route('systems.maintenance.collect', $system) }}">
                @csrf
                <button type="submit" class="btn btn-sm btn-outline-dark">Collect Maintenance</button>
            </form>
        </div>
    </div>
@endif

@if($milestones->isNotEmpty())
    @php
        $collectedAmount = $milestones->where('status', 'paid')->sum(fn ($m) => (float) $m->amount);
        $collectedPercent = $milestones->where('status', 'paid')->sum(fn ($m) => (float) $m->percentage);
        $totalPercent = $milestones->sum(fn ($m) => (float) $m->percentage);
    @endphp
    <div class="fst-card p-3 mb-3">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
            <h6 class="fw-bold text-body-secondary text-uppercase mb-0" style="font-size:.72rem;letter-spacing:.06em">
                <i class="bi bi-diagram-3 me-1"></i>Payment Stages — pay path by path
            </h6>
            <small class="text-body-secondary">
                Collected {{ \App\Models\System::formatMoney($collectedAmount, $system->price_currency) }}
                · {{ number_format($collectedPercent, 2) }}% of {{ number_format($totalPercent, 2) }}%
            </small>
        </div>

        <div class="progress mb-3" style="height:8px">
            <div class="progress-bar bg-success" role="progressbar" style="width:{{ $totalPercent > 0 ? min(100, $collectedPercent / $totalPercent * 100) : 0 }}%"></div>
        </div>

        <div class="table-responsive">
            <table class="table fst-table mb-0">
                <thead><tr><th>Payment Stage</th><th class="text-end">%</th><th class="text-end">Amount</th><th>Status</th><th>Collected</th><th class="text-end">Actions</th></tr></thead>
                <tbody>
                @foreach($milestones as $milestone)
                    <tr>
                        <td class="fw-semibold small">{{ $milestone->title }}</td>
                        <td class="text-end small">{{ rtrim(rtrim(number_format((float) $milestone->percentage, 2), '0'), '.') }}%</td>
                        <td class="text-end fw-semibold small">{{ $milestone->formatAmount() }}</td>
                        <td><x-status-pill :status="$milestone->status" /></td>
                        <td class="small text-body-secondary">{{ $milestone->paid_at?->format('M j, Y') ?? '—' }}</td>
                        <td class="text-end">
                            @if(auth()->user()->canWrite() && !$system->trashed())
                                @if($milestone->isPaid())
                                    <form method="POST" action="{{ route('systems.milestones.undo', [$system, $milestone]) }}" class="d-inline" onsubmit="fstConfirm(event, 'Undo this phase payment? The linked income transaction will be deleted.', 'Undo', 'btn-danger')">@csrf
                                        <button class="btn btn-sm btn-light border"><i class="bi bi-arrow-counterclockwise me-1"></i>Undo</button>
                                    </form>
                                @else
                                    <form method="POST" action="{{ route('systems.milestones.collect', [$system, $milestone]) }}" class="d-inline" onsubmit="fstConfirm(event, 'Collect {{ $milestone->formatAmount() }} for this phase?', 'Collect Payment', 'btn-fst-primary')">@csrf
                                        <button class="btn btn-sm btn-fst-primary"><i class="bi bi-cash-coin me-1"></i>Collect Payment</button>
                                    </form>
                                @endif
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<div class="table-responsive">
    <table class="table fst-table mb-0">
        <thead><tr><th>Date</th><th>Title</th><th>Type</th><th>Category</th><th class="text-end">Amount</th><th class="text-end">Actions</th></tr></thead>
        <tbody>
        @forelse($transactions as $tx)
            <tr>
                <td><small>{{ $tx->transaction_date->format('M j, Y') }}</small></td>
                <td>
                    <span class="small fw-semibold">{{ $tx->title }}</span>
                    @if($tx->description)<small class="d-block text-body-secondary">{{ \Illuminate\Support\Str::limit($tx->description, 80) }}</small>@endif
                </td>
                <td><x-status-pill :status="$tx->type" /></td>
                <td><small>{{ $tx->categoryLabel() }}</small></td>
                <td class="text-end fw-semibold small {{ $tx->type === 'income' ? 'text-success' : 'text-danger' }}">{{ $tx->formatAmount() }}</td>
                <td class="text-end">
                    @if(auth()->user()->canWrite())
                        <form method="POST" action="{{ route('finance.transactions.destroy', $tx) }}" class="d-inline" onsubmit="fstConfirm(event, 'Delete transaction \'{{ $tx->title }}\'?')">@csrf @method('DELETE')
                            <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                        </form>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="text-center py-4 text-body-secondary">No transactions for this system yet</td></tr>
        @endforelse
        </tbody>
    </table>
</div>

@if($transactions->hasPages())
<div class="mt-3 d-flex justify-content-end">{{ $transactions->appends(['tab' => 'finance'])->links() }}</div>
@endif

@if(auth()->user()->canWrite())
<div class="modal fade" id="financeTabModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header"><h5 class="modal-title fw-bold">Record Transaction — {{ $system->name }}</h5><button class="btn-close" data-bs-dismiss="modal"></button></div>
            <div class="modal-body">@include('admin.finance._form', ['system' => $system])</div>
        </div>
    </div>
</div>
@endif
