@php
$map = [
    'active' => 'success', 'deployed' => 'success', 'done' => 'success', 'production' => 'success',
    'paid' => 'success',
    'maintenance' => 'warning', 'in_progress' => 'warning', 'building' => 'warning', 'staging' => 'warning', 'review' => 'info', 'pending' => 'warning',
    'planning' => 'info', 'todo' => 'secondary', 'planned' => 'secondary', 'development' => 'primary',
    'failed' => 'danger', 'urgent' => 'danger', 'high' => 'danger', 'rolled_back' => 'danger',
    'archived' => 'secondary', 'issue' => 'danger', 'backup' => 'info', 'medium' => 'primary',
    'low' => 'secondary', 'other' => 'secondary',
];
$class = $map[$status] ?? 'secondary';
@endphp
<span class="fst-pill pill-{{ $class }}">{{ $label ?? ucfirst(str_replace('_', ' ', $status)) }}</span>
