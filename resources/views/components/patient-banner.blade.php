{{-- Always-visible patient banner (guideline 19.2 rule 4). Allergies and alerts are never hidden or collapsed. --}}
<div class="card border-start border-4 {{ $banner['alerts'] || $banner['allergies'] ? 'border-danger' : 'border-primary' }} mb-3 patient-banner" role="region" aria-label="{{ __('Patient banner') }}">
    <div class="card-body py-2">
        <div class="d-flex flex-wrap align-items-center gap-3">
            <div>
                <span class="h5 mb-0">{{ $banner['name'] }}</span>
                @if($banner['deceased'])<x-ui.status-badge status="deceased" class="ms-1" />@endif
            </div>
            @if($banner['number'])<span class="text-muted">{{ __('MRN:') }} <strong>{{ $banner['number'] }}</strong></span>@endif
            @if($banner['age'] || $banner['sex'])<span>{{ $banner['age'] }}@if($banner['age'] && $banner['sex']) · @endif{{ $banner['sex'] }}</span>@endif
            @if($banner['blood_group'])<span><i class="bi bi-droplet-fill text-danger" aria-hidden="true"></i> {{ $banner['blood_group'] }}</span>@endif
            @if($banner['location'])<span class="ms-auto"><i class="bi bi-geo-alt" aria-hidden="true"></i> {{ $banner['location'] }}</span>@endif
        </div>

        <div class="mt-1" data-banner-allergies>
            @if(count($banner['allergies']))
                <i class="bi bi-exclamation-triangle-fill text-danger" aria-hidden="true"></i>
                <strong class="text-danger">{{ __('Allergies:') }}</strong>
                @foreach($banner['allergies'] as $allergy)
                    <x-ui.status-badge :status="$allergy['severity'] ?? 'warning'" :label="$allergy['substance'].($allergy['severity'] ? ' ('.__($allergy['severity']).')' : '')" />
                @endforeach
            @else
                <span class="text-muted small"><i class="bi bi-check-circle" aria-hidden="true"></i> {{ __('No known allergies recorded') }}</span>
            @endif
        </div>

        @foreach($banner['alerts'] as $alert)
            <div class="mt-1" data-banner-alert>
                <x-ui.status-badge :status="$alert['severity']" :label="$alert['title']" />
            </div>
        @endforeach
    </div>
</div>
