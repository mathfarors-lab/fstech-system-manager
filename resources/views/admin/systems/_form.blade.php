<form method="POST" action="{{ isset($system) ? route('systems.update', $system) : route('systems.store') }}" class="row g-3">
    @csrf
    @isset($system) @method('PUT') @endisset

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Name <span class="text-danger">*</span></label>
        <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
               value="{{ old('name', $system->name ?? '') }}" required>
        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label class="form-label small fw-semibold">Stack <span class="text-danger">*</span></label>
        <select name="stack" class="form-select @error('stack') is-invalid @enderror">
            @foreach(\App\Models\System::STACKS as $key => $label)
                <option value="{{ $key }}" {{ old('stack', $system->stack ?? 'laravel') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label small fw-semibold">Icon (Bootstrap Icons class)</label>
        <input type="text" name="icon" class="form-control" placeholder="bi-hdd-stack-fill"
               value="{{ old('icon', $system->icon ?? '') }}">
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Environment</label>
        <select name="environment" class="form-select">
            @foreach(\App\Models\System::ENVIRONMENTS as $key => $label)
                <option value="{{ $key }}" {{ old('environment', $system->environment ?? 'development') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label small fw-semibold">Status</label>
        <select name="status" class="form-select">
            @foreach(\App\Models\System::STATUSES as $key => $label)
                <option value="{{ $key }}" {{ old('status', $system->status ?? 'active') === $key ? 'selected' : '' }}>{{ $label }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label small fw-semibold">Owner</label>
        <select name="owner_id" class="form-select">
            <option value="">— me —</option>
            @foreach($users as $user)
                <option value="{{ $user->id }}" {{ old('owner_id', $system->owner_id ?? null) == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
            @endforeach
        </select>
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Description</label>
        <textarea name="description" rows="2" class="form-control" placeholder="What does this system do?">{{ old('description', $system->description ?? '') }}</textarea>
    </div>

    <div class="col-12">
        <h6 class="fw-bold text-body-secondary text-uppercase mb-2" style="font-size:.72rem;letter-spacing:.06em"><i class="bi bi-person-vcard me-1"></i>Client Information</h6>
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Client name</label>
        <input type="text" name="client_name" class="form-control" placeholder="Full name"
               value="{{ old('client_name', $system->client_name ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Phone number</label>
        <input type="text" name="client_phone" class="form-control" placeholder="+855 12 345 678"
               value="{{ old('client_phone', $system->client_phone ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Telegram</label>
        <input type="text" name="client_telegram" class="form-control" placeholder="@username"
               value="{{ old('client_telegram', $system->client_telegram ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Email</label>
        <input type="email" name="client_email" class="form-control" placeholder="client@email.com"
               value="{{ old('client_email', $system->client_email ?? '') }}">
    </div>

    <div class="col-md-8">
        <label class="form-label small fw-semibold">Address</label>
        <input type="text" name="client_address" class="form-control" placeholder="Street, city, province..."
               value="{{ old('client_address', $system->client_address ?? '') }}">
    </div>

    @unless(isset($system))
        <div class="col-12">
            <div class="border rounded p-3">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <h6 class="fw-bold text-body-secondary text-uppercase mb-0" style="font-size:.72rem;letter-spacing:.06em">
                        <i class="bi bi-wallet2 me-1"></i>Project Costs <span class="text-danger">*</span>
                    </h6>
                    <div class="d-flex align-items-center gap-2">
                        <label class="form-label small fw-semibold mb-0" for="expenseCurrency">Currency</label>
                        <select name="expense_currency" id="expenseCurrency" class="form-select form-select-sm" style="width:auto">
                            <option value="USD" {{ old('expense_currency', 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="KHR" {{ old('expense_currency') === 'KHR' ? 'selected' : '' }}>KHR</option>
                        </select>
                    </div>
                </div>
                <div id="expenseRows"></div>
                <button type="button" class="btn btn-light border btn-sm mt-1" onclick="fstAddExpenseRow()"><i class="bi bi-plus-lg me-1"></i>Add Cost</button>
            </div>
        </div>
    @endunless

    <div class="col-12">
        <h6 class="fw-bold text-body-secondary text-uppercase mt-2 mb-2" style="font-size:.72rem;letter-spacing:.06em"><i class="bi bi-diagram-3 me-1"></i>Payment Stage Plan</h6>
    </div>

    <div class="col-12">
        <div class="border rounded p-3">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                <h6 class="fw-bold text-body-secondary text-uppercase mb-0" style="font-size:.72rem;letter-spacing:.06em">
                    <i class="bi bi-diagram-3 me-1"></i>Payment Stage
                </h6>
                <span class="small text-body-secondary">Total: <strong id="milestonePercentTotal">—</strong></span>
            </div>
            <div id="milestoneRows"></div>
            <button type="button" class="btn btn-light border btn-sm mt-1" onclick="fstAddMilestoneRow()"><i class="bi bi-plus-lg me-1"></i>Add Stage</button>
            <div class="form-text">e.g. Start to build 40%, Testing 20%, Complete 40%. Percentages must total 100%.</div>
            @error('milestones')<div class="text-danger small mt-1">{{ $message }}</div>@enderror
        </div>
    </div>

    <div class="col-12">
        <h6 class="fw-bold text-body-secondary text-uppercase mt-2 mb-2" style="font-size:.72rem;letter-spacing:.06em"><i class="bi bi-cash-coin me-1"></i>System Pricing</h6>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">System price</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" name="price" id="priceInput" class="form-control" placeholder="0.00"
                   value="{{ old('price', $system->price ?? '') }}">
            <select name="price_currency" id="priceCurrency" class="form-select" style="max-width:90px">
                @foreach(\App\Models\System::CURRENCIES as $currency)
                    <option value="{{ $currency }}" {{ old('price_currency', $system->price_currency ?? 'USD') === $currency ? 'selected' : '' }}>{{ $currency }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-text">Automatically calculated from Project Costs.</div>
    </div>

    <div class="col-12">
        <h6 class="fw-bold text-body-secondary text-uppercase mt-2 mb-2" style="font-size:.72rem;letter-spacing:.06em"><i class="bi bi-gear me-1"></i>Maintenance Plan</h6>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Maintenance amount</label>
        <div class="input-group">
            <input type="number" step="0.01" min="0" name="maintenance_amount" id="maintenanceAmount" class="form-control" placeholder="0.00"
                   value="{{ old('maintenance_amount', $system->maintenance_amount ?? '') }}">
            <select name="maintenance_cycle" id="maintenanceCycle" class="form-select" style="max-width:140px">
                <option value="">— cycle —</option>
                @foreach(\App\Models\System::MAINTENANCE_CYCLES as $key => $label)
                    <option value="{{ $key }}" {{ old('maintenance_cycle', $system->maintenance_cycle ?? '') === $key ? 'selected' : '' }}>{{ $label }}</option>
                @endforeach
            </select>
        </div>
        <div class="form-text" id="maintenanceSummary">—</div>
    </div>

    <div class="col-md-6">
        <label class="form-label small fw-semibold">Maintenance starts on</label>
        <input type="date" name="maintenance_starts_at" class="form-control"
               value="{{ old('maintenance_starts_at', isset($system) && $system->maintenance_starts_at ? $system->maintenance_starts_at->format('Y-m-d') : '') }}">
    </div>

    <div class="col-md-8">
        <label class="form-label small fw-semibold">Payment notes</label>
        <input type="text" name="payment_notes" class="form-control" placeholder="e.g. Deposit paid, remaining on delivery (2 parts)..."
               value="{{ old('payment_notes', $system->payment_notes ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Folder path</label>
        <input type="text" name="folder_path" class="form-control" placeholder="D:\SYSTEM\Poss-System"
               value="{{ old('folder_path', $system->folder_path ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Repository URL</label>
        <input type="url" name="repo_url" class="form-control" placeholder="https://github.com/..."
               value="{{ old('repo_url', $system->repo_url ?? '') }}">
    </div>

    <div class="col-md-4">
        <label class="form-label small fw-semibold">Deploy URL</label>
        <input type="url" name="deploy_url" class="form-control" placeholder="https://..."
               value="{{ old('deploy_url', $system->deploy_url ?? '') }}">
    </div>

    <div class="col-12">
        <label class="form-label small fw-semibold">Notes</label>
        <textarea name="notes" rows="3" class="form-control">{{ old('notes', $system->notes ?? '') }}</textarea>
    </div>

    <div class="col-12 d-flex justify-content-end gap-2 border-top pt-3">
        <a href="{{ route('systems.index') }}" class="btn btn-light border">Cancel</a>
        <button type="submit" class="btn btn-fst-primary px-4">
            {{ isset($system) ? 'Save Changes' : 'Create System' }}
        </button>
    </div>
</form>

<script>
    let fstExpenseIndex = 0;
    let fstMilestoneIndex = 0;
    const fstMaintenanceCycles = @json(\App\Models\System::MAINTENANCE_CYCLES);

    function fstMoney(value, currency) {
        const n = Number(value || 0);

        return currency === 'KHR'
            ? 'KHR ' + n.toLocaleString('en-US', { maximumFractionDigits: 0 })
            : '$' + n.toLocaleString('en-US', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    }

    function fstCurrentPrice() {
        let total = 0;
        document.querySelectorAll('#expenseRows .expense-amount').forEach(input => {
            total += parseFloat(input.value) || 0;
        });

        return { amount: total, currency: document.getElementById('expenseCurrency')?.value || 'USD' };
    }

    function fstUpdateExpenseSummary() {
        const price = fstCurrentPrice();
        const el = document.getElementById('priceInput');
        const totalEl = document.getElementById('expenseTotal'); // Note: Removed total display in this view, keeping logic
        
        if (el) {
            el.value = price.amount.toFixed(2);
        }

        fstUpdateMilestones();
    }

    function fstUpdateMaintenanceSummary() {
        const amount = parseFloat(document.getElementById('maintenanceAmount')?.value) || 0;
        const cycle = document.getElementById('maintenanceCycle')?.value || '';
        const el = document.getElementById('maintenanceSummary');

        el.textContent = amount > 0
            ? fstMoney(amount, fstCurrentPrice().currency) + (cycle ? ' / ' + fstMaintenanceCycles[cycle] : '')
            : '—';
    }

    function fstUpdateMilestones() {
        const wrap = document.getElementById('milestoneRows');

        if (!wrap) {
            return;
        }

        const price = fstCurrentPrice();
        let percent = 0;

        wrap.querySelectorAll('.milestone-row').forEach(row => {
            const pct = parseFloat(row.querySelector('.milestone-pct').value) || 0;
            percent += pct;
            row.querySelector('.milestone-amount').textContent = price.amount > 0 && pct > 0
                ? fstMoney(price.amount * pct / 100, price.currency)
                : '—';
        });

        const el = document.getElementById('milestonePercentTotal');
        const rounded = Math.round(percent * 100) / 100;

        el.textContent = rounded + '%';
        el.classList.toggle('text-success', Math.abs(rounded - 100) < 0.01);
        el.classList.toggle('text-danger', Math.abs(rounded - 100) >= 0.01);
    }

    function fstAddMilestoneRow(title = '', percentage = '') {
        const wrap = document.getElementById('milestoneRows');
        const idx = fstMilestoneIndex++;
        const row = document.createElement('div');
        row.className = 'milestone-row row g-2 mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-5">
                <input type="text" name="milestones[${idx}][title]" class="form-control form-control-sm" placeholder="Phase name (e.g. Testing)" maxlength="100" required>
            </div>
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <input type="number" step="0.01" min="0" max="100" name="milestones[${idx}][percentage]" class="form-control milestone-pct" placeholder="0" required>
                    <span class="input-group-text">%</span>
                </div>
            </div>
            <div class="col-md-3">
                <span class="milestone-amount small fw-semibold text-body-secondary">—</span>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-light border" title="Remove" onclick="this.closest('.milestone-row').remove(); fstUpdateMilestones();"><i class="bi bi-x-lg"></i></button>
            </div>`;
        row.querySelector('input[name*="[title]"]').value = title;
        row.querySelector('.milestone-pct').value = percentage;
        wrap.appendChild(row);
        fstUpdateMilestones();
    }

    function fstAddExpenseRow(category = 'hosting') {
        const wrap = document.getElementById('expenseRows');
        const idx = fstExpenseIndex++;
        const row = document.createElement('div');
        row.className = 'expense-row row g-2 mb-2 align-items-center';
        row.innerHTML = `
            <div class="col-md-3">
                <select name="expenses[${idx}][category]" class="form-select form-select-sm expense-category">
                    @foreach(\App\Models\FinanceTransaction::EXPENSE_CATEGORIES as $cat)
                        <option value="{{ $cat }}">{{ \App\Models\System::CATEGORY_LABELS[$cat] ?? $cat }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5">
                <input type="text" name="expenses[${idx}][title]" class="form-control form-control-sm" placeholder="Description (optional)" maxlength="255">
            </div>
            <div class="col-md-3">
                <input type="number" step="0.01" min="0" name="expenses[${idx}][amount]" class="form-control form-control-sm expense-amount" placeholder="0.00" required>
            </div>
            <div class="col-md-1 text-end">
                <button type="button" class="btn btn-sm btn-light border" title="Remove" onclick="this.closest('.expense-row').remove(); fstUpdateExpenseSummary();"><i class="bi bi-x-lg"></i></button>
            </div>`;
        row.querySelector('.expense-category').value = category;
        wrap.appendChild(row);
        fstUpdateExpenseSummary();
    }

    const fstExpenseRows = document.getElementById('expenseRows');

    if (fstExpenseRows) {
        fstExpenseRows.addEventListener('input', fstUpdateExpenseSummary);
        fstExpenseRows.addEventListener('change', fstUpdateExpenseSummary);
        document.getElementById('expenseCurrency').addEventListener('change', fstUpdateExpenseSummary);

        fstAddExpenseRow('hosting');
        fstAddExpenseRow('domain');
        fstAddExpenseRow('setup_fee');
    }

    const fstMilestoneRows = document.getElementById('milestoneRows');

    if (fstMilestoneRows) {
        fstMilestoneRows.addEventListener('input', fstUpdateMilestones);
        fstMilestoneRows.addEventListener('change', fstUpdateMilestones);

        fstAddMilestoneRow('Start to build', 40);
        fstAddMilestoneRow('Testing', 20);
        fstAddMilestoneRow('Complete', 40);
    }

    document.getElementById('maintenanceAmount').addEventListener('input', fstUpdateMaintenanceSummary);
    document.getElementById('maintenanceCycle').addEventListener('change', fstUpdateMaintenanceSummary);

    fstUpdateMaintenanceSummary();
    fstUpdateMilestones();
</script>