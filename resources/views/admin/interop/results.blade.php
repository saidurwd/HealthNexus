@extends('layouts.adminlte')

@section('page_title', __('Results received from other laboratories'))

@section('page_content')
    @include('admin.interop._flash')
    <p class="text-muted small">{{ __('These came in by HL7. They are NOT in the patient\'s record: nothing is added by itself. A person looks, then enters what is right through the usual laboratory screens.') }}</p>
    <form method="GET" class="mb-3"><select name="status" class="form-select w-auto" aria-label="{{ __('Status') }}" onchange="this.form.submit()">@foreach(['new' => 'Waiting', 'accepted' => 'Accepted', 'rejected' => 'Rejected', 'all' => 'All'] as $k => $t)<option value="{{ $k }}" @selected($status === $k)>{{ __($t) }}</option>@endforeach</select></form>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Test') }}</th><th>{{ __('Result') }}</th><th>{{ __('Range') }}</th><th>{{ __('Flag') }}</th><th>{{ __('Taken') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
        <tbody>@forelse($rows as $r)<tr class="{{ in_array($r->abnormal_flag, ['AA', 'HH', 'LL']) ? 'table-danger' : '' }}"><td>{{ $r->patient?->first_name }} {{ $r->patient?->last_name }} <span class="text-muted">{{ $r->patient?->enterprise_patient_no }}</span></td><td>{{ $r->name ?: $r->code }}</td><td><strong>{{ $r->value }}</strong> {{ $r->unit }}</td><td class="text-muted">{{ $r->reference_range }}</td><td>{{ $r->abnormal_flag }}</td><td>{{ $r->observed_at?->format('d M H:i') }}</td><td><x-ui.status-badge :status="$r->status" /></td>
            <td>@can('interoperability.hl7.review')@if($r->status === 'new')<form method="POST" action="{{ route('admin.interop.results.review', $r->public_id) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Note') }}" aria-label="{{ __('Note') }}"><button name="decision" value="accepted" class="btn btn-sm btn-outline-success">{{ __('Accept') }}</button><button name="decision" value="rejected" class="btn btn-sm btn-outline-danger">{{ __('Reject') }}</button></form>@endif @endcan</td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('Nothing waiting.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
