@extends('layouts.adminlte')

@section('page_title', __('Donor').' '.$donor->donor_number)

@section('page_content')
    @include('admin.blood._flash')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ $donor->patient->full_name }}</h3></div>
                <div class="card-body"><dl class="row mb-0">
                    <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$donor->status" /> @if($donor->deferred_until)<small class="text-muted">{{ __('until') }} {{ $donor->deferred_until->format('d M Y') }}</small>@endif</dd>
                    <dt class="col-5">{{ __('Blood group') }}</dt><dd class="col-7">{{ $donor->abo ? $donor->abo.' '.__(\Illuminate\Support\Str::headline($donor->rh)) : '—' }}</dd>
                    <dt class="col-5">{{ __('Donations') }}</dt><dd class="col-7">{{ $donor->donation_count }}</dd>
                    <dt class="col-5">{{ __('Last donation') }}</dt><dd class="col-7">{{ $donor->last_donation_at?->format('d M Y') ?? '—' }}</dd>
                    <dt class="col-5">{{ __('Consent') }}</dt><dd class="col-7">{{ $donor->consent_at?->format('d M Y') ?? '—' }}</dd>
                </dl></div></div>
            @can('bloodbank.lookback.view')
                <a class="btn btn-outline-secondary" href="{{ route('admin.blood.lookback', ['q' => $donor->donor_number]) }}">{{ __('Who received this donor’s blood?') }}</a>
            @endcan
        </div>
        <div class="col-lg-7">
            @can('bloodbank.screening.perform')
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Screening before donation') }}</h3></div>
                <form method="POST" action="{{ route('admin.blood.donors.screen', $donor) }}" data-enter-next>
                    @csrf
                    <div class="card-body row g-2">
                        @foreach([['weight_kg', 'Weight (kg)'], ['hemoglobin', 'Hemoglobin (g/dL)'], ['systolic', 'Systolic (mmHg)'], ['diastolic', 'Diastolic (mmHg)'], ['pulse', 'Pulse Rate (bpm)'], ['temperature_c', 'Temperature (°C)']] as [$f, $l])
                            <div class="col-6 col-md-4"><label class="form-label">{{ __($l) }}</label><input type="number" step="0.1" name="{{ $f }}" class="form-control"></div>
                        @endforeach
                        <div class="col-12"><div class="form-label">{{ __('Does any of these apply?') }}</div>
                            @foreach($deferralReasons as $reason)@continue(in_array($reason, ['low_hemoglobin', 'underweight', 'recent_donation', 'positive_screen', 'other']))
                                <label class="form-check form-check-inline"><input type="hidden" name="questionnaire[{{ $reason }}]" value="0"><input type="checkbox" name="questionnaire[{{ $reason }}]" value="1" class="form-check-input"> {{ __(\Illuminate\Support\Str::headline($reason)) }}</label>
                            @endforeach</div>
                    </div>
                    <div class="card-footer"><button class="btn btn-outline-danger">{{ __('Check eligibility') }}</button></div>
                </form></div>
            @endcan
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recent screenings') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($screenings as $s)
                        <li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $s->screened_at->format('d M H:i') }} — <strong>{{ __(\Illuminate\Support\Str::headline($s->outcome)) }}</strong> @if($s->failed_rules)<span class="text-danger">{{ implode(', ', array_map(fn ($r) => __(\Illuminate\Support\Str::headline($r)), $s->failed_rules)) }}</span>@endif</span>
                            @if($s->outcome === 'eligible' && $s->screened_at->gt(now()->subDay()) && ! $donor->isDeferred())@can('bloodbank.donation.collect')<form method="POST" action="{{ route('admin.blood.donors.donate', $donor) }}">@csrf<input type="hidden" name="screening" value="{{ $s->public_id }}"><input type="text" name="bag_lot" class="form-control form-control-sm d-inline w-auto" placeholder="{{ __('Bag lot') }}" maxlength="40"> <button class="btn btn-sm btn-danger">{{ __('Start donation') }}</button></form>@endcan @endif</li>
                    @empty<li class="list-group-item text-muted">{{ __('No screenings yet.') }}</li>@endforelse
                </ul></div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Donations') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($donations as $d)<li class="list-group-item"><a href="{{ route('admin.blood.donations.show', $d) }}">{{ $d->donation_number }}</a> — {{ $d->started_at->format('d M Y H:i') }} · <x-ui.status-badge :status="$d->status" /></li>
                    @empty<li class="list-group-item text-muted">{{ __('No donations yet.') }}</li>@endforelse
                </ul></div>
        </div>
    </div>
@stop
