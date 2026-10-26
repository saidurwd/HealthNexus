@extends('layouts.adminlte')

@section('page_title', __('Donation').' '.$donation->donation_number)

@section('page_content')
    @include('admin.blood._flash')
    <div class="row g-3">
        <div class="col-lg-5">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ $donation->donor->patient->full_name }} · <a href="{{ route('admin.blood.donors.show', $donation->donor) }}">{{ $donation->donor->donor_number }}</a></h3></div>
                <div class="card-body"><dl class="row mb-0">
                    <dt class="col-5">{{ __('Status') }}</dt><dd class="col-7"><x-ui.status-badge :status="$donation->status" /></dd>
                    <dt class="col-5">{{ __('Started') }}</dt><dd class="col-7">{{ $donation->started_at->format('d M H:i') }}</dd>
                    <dt class="col-5">{{ __('Volume') }}</dt><dd class="col-7">{{ $donation->volume_ml ? $donation->volume_ml.' ml' : '—' }}</dd>
                    <dt class="col-5">{{ __('Rest period') }}</dt><dd class="col-7">@if($donation->observation_done_at){{ __('Finished') }} {{ $donation->observation_done_at->format('H:i') }}@elseif($donation->observation_until){{ __('until') }} {{ $donation->observation_until->format('H:i') }}@else — @endif</dd>
                </dl></div>
                @can('bloodbank.donation.collect')
                <div class="card-footer d-flex flex-wrap gap-2">
                    @if($donation->status === 'collecting')
                        <form method="POST" action="{{ route('admin.blood.donations.complete', $donation) }}" class="d-flex gap-2">@csrf<input type="number" name="volume_ml" class="form-control" style="width:110px" value="{{ config('blood.collection_volume_ml') }}" min="100" max="600" required><button class="btn btn-danger">{{ __('Collection finished') }}</button></form>
                        <form method="POST" action="{{ route('admin.blood.donations.abort', $donation) }}" class="d-flex gap-2">@csrf<input name="reason" class="form-control" placeholder="{{ __('Why stopped?') }}" required maxlength="200"><button class="btn btn-outline-secondary">{{ __('Stop') }}</button></form>
                    @elseif($donation->status === 'collected' && ! $donation->observation_done_at)
                        <form method="POST" action="{{ route('admin.blood.donations.observation', $donation) }}">@csrf<button class="btn btn-outline-primary">{{ __('Rest period finished') }}</button></form>
                    @endif
                </div>
                @endcan
            </div>
            @can('bloodbank.donation.collect')
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Donor reaction') }}</h3></div>
                <form method="POST" action="{{ route('admin.blood.donations.adverse', $donation) }}" class="card-body d-flex flex-wrap gap-2">@csrf
                    <select name="event_type" class="form-select w-auto">@foreach($adverseTypes as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select>
                    <select name="severity" class="form-select w-auto"><option value="mild">{{ __('Mild') }}</option><option value="moderate">{{ __('Moderate') }}</option><option value="severe">{{ __('Severe') }}</option></select>
                    <input name="description" class="form-control" maxlength="500" placeholder="{{ __('What happened') }}"><input name="action_taken" class="form-control" maxlength="500" placeholder="{{ __('What was done') }}"><button class="btn btn-outline-danger">{{ __('Record') }}</button></form>
                <ul class="list-group list-group-flush small">@foreach($adverseEvents as $e)<li class="list-group-item">{{ $e->occurred_at->format('H:i') }} — {{ __(\Illuminate\Support\Str::headline($e->event_type)) }} ({{ __(\Illuminate\Support\Str::headline($e->severity)) }}) {{ $e->description }}</li>@endforeach</ul></div>
            @endcan
        </div>
        <div class="col-lg-7">
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Tests (read from the laboratory)') }}</h3></div>
                <ul class="list-group list-group-flush small">
                    @forelse($donation->tests as $t)
                        <li class="list-group-item d-flex justify-content-between"><span>{{ $t->test_code }}</span><span>{{ $t->value ?? '—' }} <span class="badge text-bg-{{ ['pass' => 'success', 'fail' => 'danger', 'discordant' => 'warning', 'pending' => 'secondary'][$t->outcome] ?? 'secondary' }}">{{ __(\Illuminate\Support\Str::headline($t->outcome)) }}</span></span></li>
                    @empty<li class="list-group-item text-muted">{{ __('Tests are ordered when the collection is finished.') }}</li>@endforelse
                </ul>
                <div class="card-footer d-flex flex-wrap gap-2">
                    @can('bloodbank.test.record')<form method="POST" action="{{ route('admin.blood.donations.sync', $donation) }}">@csrf<button class="btn btn-outline-secondary">{{ __('Read results from the laboratory') }}</button></form>@endcan
                    @can('bloodbank.unit.process')<form method="POST" action="{{ route('admin.blood.donations.separate', $donation) }}">@csrf<button class="btn btn-outline-primary">{{ __('Make components') }}</button></form>@endcan
                    @can('bloodbank.unit.release')<form method="POST" action="{{ route('admin.blood.donations.release', $donation) }}" onsubmit="return confirm('{{ __('Release all tested units to the shelf?') }}')">@csrf<button class="btn btn-success">{{ __('Release to the shelf') }}</button></form>@endcan
                </div>
            </div>
            <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Bags') }}</h3></div>
                <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Unit') }}</th><th>{{ __('Component') }}</th><th>{{ __('Group') }}</th><th>{{ __('Status') }}</th><th>{{ __('Expires') }}</th></tr></thead><tbody>
                    @foreach($units as $u)<tr><td><a href="{{ route('admin.blood.units.show', $u) }}">{{ $u->unit_number }}</a></td><td>{{ __(config("blood.components.{$u->component}.label")) }}</td><td>{{ $u->abo ? $u->abo.' '.__(\Illuminate\Support\Str::headline($u->rh)) : '—' }}</td><td><x-ui.status-badge :status="$u->status" /></td><td>{{ $u->expires_at->format('d M Y') }}</td></tr>@endforeach
                </tbody></table></div></div>
        </div>
    </div>
@stop
