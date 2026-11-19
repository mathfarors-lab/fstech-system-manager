@props(['title', 'subtitle' => null, 'hover' => false])

<div {{ $attributes->merge(['class' => 'fst-card p-4' . ($hover ? ' fst-card-hover' : '')]) }}>
    <div class="d-flex align-items-start justify-content-between mb-3">
        <div>
            <h5 class="fw-bold mb-0">{{ $title }}</h5>
            @if($subtitle)
                <small class="text-body-secondary">{{ $subtitle }}</small>
            @endif
        </div>
        <div class="d-flex gap-2">
            @isset($actions)
                {{ $actions }}
            @endisset
        </div>
    </div>
    {{ $slot }}
</div>
