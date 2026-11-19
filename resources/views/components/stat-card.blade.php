@props(['icon', 'value', 'label', 'color' => 'primary'])

<div class="fst-card fst-stat-card fst-card-hover p-3 d-flex align-items-center gap-3">
    <span class="stat-icon" style="background:rgba(99,102,241,.12);color:var(--fst-primary)"><i class="bi {{ $icon }}"></i></span>
    <div>
        <div class="stat-value">{{ $value }}</div>
        <div class="stat-label">{{ $label }}</div>
    </div>
</div>
