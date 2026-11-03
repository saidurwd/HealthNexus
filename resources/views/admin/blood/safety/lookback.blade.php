@extends('layouts.adminlte')

@section('page_title', __('Lookback and recall'))

@section('page_content')
    @include('admin.blood._flash')
    <form method="GET" class="d-flex gap-2 mb-3"><input type="search" name="q" value="{{ $q }}" class="form-control" placeholder="{{ __('Donor number or bag number') }}" autofocus><button class="btn btn-danger">{{ __('Trace') }}</button></form>
    @if($donor)<div class="alert alert-info">{{ __('Donor') }} <strong>{{ $donor->donor_number }}</strong> — {{ $donor->patient->full_name }}</div>@endif
    @if($unit)<div class="alert alert-info">{{ __('Bag') }} <strong>{{ $unit->unit_number }}</strong> — {{ __(config("blood.components.{$unit->component}.label")) }} · @if($link && $link['donor'])<a href="{{ route('admin.blood.donors.show', $link['donor']) }}">{{ __('given by donor') }} {{ $link['donor']->donor_number }}</a>@else {{ __('donor not on record') }} @endif</div>@endif
    @if($q !== '')
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Who received it') }} ({{ $recipients->count() }})</h3></div>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Number') }}</th><th>{{ __('Bag') }}</th><th>{{ __('Component') }}</th><th>{{ __('Issued') }}</th><th>{{ __('State') }}</th></tr></thead><tbody>
            @forelse($recipients as $r)<tr><td>{{ $r['patient'] }}</td><td>{{ $r['mrn'] }}</td><td>{{ $r['unit_number'] }}</td><td>{{ __(config("blood.components.{$r['component']}.label")) }}</td><td>{{ $r['issued_at'] }}</td><td>{{ __(\Illuminate\Support\Str::headline($r['state'])) }}</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('No one has received these bags.') }}</td></tr>@endforelse
        </tbody></table></div></div>
    @endif
    @can('bloodbank.recall.manage')
    <form method="POST" action="{{ route('admin.blood.recall') }}" class="card border-danger" onsubmit="return confirm('{{ __('Recall all bags of this donor? Unused bags are blocked now.') }}')">@csrf
        <div class="card-header"><h3 class="card-title text-danger">{{ __('Recall a donor') }}</h3></div>
        <div class="card-body d-flex flex-wrap gap-2"><input name="donor" class="form-control w-auto" placeholder="{{ __('Donor number') }}" value="{{ $donor?->donor_number }}" required><input name="reason" class="form-control w-auto flex-grow-1" placeholder="{{ __('Reason') }}" required maxlength="500"><button class="btn btn-danger">{{ __('Recall') }}</button></div></form>
    @endcan
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Recent recalls') }}</h3></div>
        <ul class="list-group list-group-flush small">@forelse($recalls as $r)<li class="list-group-item">{{ $r->initiated_at->format('d M Y H:i') }} — {{ $r->reason }} · {{ $r->units_blocked }} {{ __('bags blocked') }}, {{ $r->recipients_found }} {{ __('recipients') }}</li>@empty<li class="list-group-item text-muted">{{ __('No recalls.') }}</li>@endforelse</ul></div>
@stop
