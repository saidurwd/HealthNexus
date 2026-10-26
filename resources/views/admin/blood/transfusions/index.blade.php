@extends('layouts.adminlte')

@section('page_title', __('Transfusions'))

@section('page_content')
    @include('admin.blood._flash')
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Waiting to be started') }}</h3></div>
        <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Bag') }}</th><th>{{ __('Issued') }}</th><th>{{ __('Bedside check') }}</th></tr></thead><tbody>
        @forelse($waiting as $i)
            <tr><td>{{ $i->patient->full_name }} <small class="text-muted">{{ $i->patient->enterprise_patient_no }}</small></td><td>{{ $i->unit->unit_number }}</td><td>{{ $i->issued_at->format('d M H:i') }}</td>
                <td>@can('bloodbank.transfusion.record')
                    <form method="POST" action="{{ route('admin.blood.transfusions.start', $i) }}" class="d-flex flex-wrap gap-1" autocomplete="off">@csrf
                        <input name="wristband_scan" class="form-control form-control-sm" style="width:150px" placeholder="{{ __('Scan wristband') }}" required><input name="bag_scan" class="form-control form-control-sm" style="width:170px" placeholder="{{ __('Scan bag') }}" required>
                        <input type="email" name="second_checker_email" class="form-control form-control-sm" style="width:170px" placeholder="{{ __('Second checker e-mail') }}" required><input type="password" name="second_checker_password" class="form-control form-control-sm" style="width:130px" placeholder="{{ __('Their password') }}" required>
                        <input name="override_reason" class="form-control form-control-sm" style="width:170px" placeholder="{{ __('Override reason (only if the check fails)') }}" maxlength="300"><button class="btn btn-sm btn-danger">{{ __('Start') }}</button></form>@endcan</td></tr>
        @empty<tr><td colspan="4" class="text-muted">{{ __('No bags are waiting on the ward.') }}</td></tr>@endforelse
        </tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Running now') }}</h3></div>
        <ul class="list-group list-group-flush">@forelse($running as $t)<li class="list-group-item"><a href="{{ route('admin.blood.transfusions.show', $t) }}">{{ $t->issue->patient->full_name }}</a> — {{ $t->issue->unit->unit_number }} · {{ __('started') }} {{ $t->started_at->format('H:i') }}</li>@empty<li class="list-group-item text-muted">{{ __('No transfusion is running.') }}</li>@endforelse</ul></div>
@stop
