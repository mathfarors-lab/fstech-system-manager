@php $tx = isset($transaction) && $transaction ? $transaction : null; @endphp

<form method="POST"
      action="{{ $tx ? route('finance.transactions.update', $tx) : (! empty($system) ? url('/finance/transactions') : route('finance.transactions.store')) }}"
      class="row g-3">
    @csrf
    @if($tx) @method('PUT') @endif

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Type <span class="text-danger">*</span></label>
        <select name="type" class="form-select tx-type" required>
            @foreach(\App\Models\FinanceTransaction::TYPES as $key => $label)
                <option value="{{ $key }}" {{ old('type', $tx?->type ?? 'income') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Category <span class="text-danger">*</span></label>
        <select name="category" class="form-select tx-category" required>
            @foreach(\App\Models\FinanceTransaction::CATEGORIES as $key => $label)
                @php $group = in_array($key, \App\Models\FinanceTransaction::INCOME_CATEGORIES) ? 'cat-income' : 'cat-expense'; @endphp
                <option class="{{ $group }}" value="{{ $key }}" {{ old('category', $tx?->category ?? 'client_payment') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-8">
        <label class="form-label small fw-semibold">Title <span class="text-danger">*</span></label>
        <input type="text" name="title" class="form-control" placeholder="e.g. Poss-System maintenance — September"
               value="{{ old('title', $tx?->title ?? '') }}" required>
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Amount <span class="text-danger">*</span></label>
        <div class="input-group">
            <input type="number" name="amount" step="0.01" min="0.01" class="form-control"
                   value="{{ old('amount', $tx?->amount ?? '') }}" required>
            <select name="currency" class="form-select" style="max-width:110px">
                @foreach(['USD' => 'USD $', 'KHR' => 'KHR ៛'] as $key => $label)
                    <option value="{{ $key }}" {{ old('currency', $tx?->currency ?? 'USD') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
    </div>

    @isset($system)
        <input type="hidden" name="system_id" value="{{ $system->id }}">
    @else
        <div class="col-md-6">
            <label class="form-label small fw-semibold">System</label>
            <select name="system_id" class="form-select">
                <option value="">— general / no specific system —</option>
                @foreach($systems as $s)
                    <option value="{{ $s->id }}" {{ old('system_id', $tx?->system_id) == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label small fw-semibold">Date <span class="text-danger">*</span></label>
            <input type="date" name="transaction_date" class="form-control"
                   value="{{ old('transaction_date', $tx?->transaction_date?->format('Y-m-d') ?? date('Y-m-d')) }}" required>
        </div>
    @endisset

    <div class="col-12">
        <label class="form-label small fw-semibold">Description</label>
        <textarea name="description" rows="2" class="form-control">{{ old('description', $tx?->description ?? '') }}</textarea>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
        <button type="button" class="btn btn-light border" data-bs-dismiss="modal">Cancel</button>
        <button class="btn btn-fst-primary px-4">{{ $tx ? 'Save Changes' : 'Record Transaction' }}</button>
    </div>
</form>

@if(! isset($skipTxJs) || ! $skipTxJs)
<script>
(function () {
    if (window.__fstTxJs) return;
    window.__fstTxJs = true;
    document.addEventListener('change', function (e) {
        const type = e.target.closest('.tx-type');
        if (! type) return;
        const form = type.closest('form');
        const isIncome = type.value === 'income';
        form.querySelectorAll('.tx-category .cat-income').forEach(o => o.disabled = !isIncome);
        form.querySelectorAll('.tx-category .cat-expense').forEach(o => o.disabled = isIncome);
        const sel = form.querySelector('.tx-category');
        if (sel.selectedOptions[0]?.disabled) {
            const valid = sel.querySelector(isIncome ? '.cat-income:not(:disabled)' : '.cat-expense:not(:disabled)');
            if (valid) sel.value = valid.value;
        }
    });
    document.querySelectorAll('.tx-type').forEach(el => el.dispatchEvent(new Event('change', { bubbles: true })));
})();
</script>
@endif
