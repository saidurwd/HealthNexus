@props(['title' => null, 'hint' => null, 'icon' => 'bi-inbox'])
{{-- Honest empty state (rule 11): say what is missing and what to do next. --}}
<div {{ $attributes->merge(['class' => 'text-center text-muted py-4']) }}>
    <i class="bi {{ $icon }} fs-1" aria-hidden="true"></i>
    <div class="fw-semibold mt-2">{{ $title ?? __('Nothing here yet.') }}</div>
    @if($hint)<div class="small">{{ $hint }}</div>@endif
    {{ $slot }}
</div>
