@props([
    'variant' => 'mark',
    'height' => 36,
    'width' => null,
    'rounded' => true,
    'pdf' => false,
    'alt' => 'FS Tech Systems',
])

@php
    $file = $variant === 'banner' ? 'images/fs-tech-banner.png' : 'images/fs-tech-logo.png';
    $radius = $rounded ? 'border-radius:8px;' : '';
    $src = $pdf ? str_replace('\\', '/', public_path($file)) : asset($file);

    if ($width) {
        $size = 'width:'.$width.';height:auto;';
    } elseif ($variant === 'banner') {
        $size = 'width:100%;height:auto;';
    } else {
        $size = 'height:'.(int) $height.'px;width:auto;';
    }
@endphp

@if(is_file(public_path($file)))
    <img src="{{ $src }}" alt="{{ $alt }}"
         {{ $attributes->merge(['style' => $size.'object-fit:contain;'.$radius]) }}>
@else
    <span {{ $attributes->merge(['class' => 'fst-brand-icon', 'style' => 'width:'.(int) $height.'px;height:'.(int) $height.'px;flex-basis:'.(int) $height.'px;font-size:'.max(12, (int) round($height * 0.45)).'px;'.$radius]) }}>
        <i class="bi bi-grid-1x2-fill"></i>
    </span>
@endif
