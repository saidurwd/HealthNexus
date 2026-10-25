@props(['label', 'value', 'icon' => 'bi-activity', 'variant' => 'primary', 'href' => null])
<div class="col">
    <div class="card h-100">
        <div class="card-body d-flex align-items-center gap-3">
            <i class="bi {{ $icon }} fs-2 text-{{ $variant }}" aria-hidden="true"></i>
            <div>
                <div class="h4 mb-0">{{ $value }}</div>
                @if($href)<a href="{{ $href }}" class="small stretched-link text-decoration-none text-muted">{{ $label }}</a>@else<span class="small text-muted">{{ $label }}</span>@endif
            </div>
        </div>
    </div>
</div>
