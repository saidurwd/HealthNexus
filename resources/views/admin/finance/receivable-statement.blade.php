@extends('layouts.adminlte')

@section('page_title', __('Statement').': '.$name)

@section('page_content')
    @include('admin.finance._flash')
    @php($m = fn ($v) => number_format((float) $v, 2))
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Document') }}</th><th>{{ __('Date') }}</th><th>{{ __('Due') }}</th><th class="text-end">{{ __('Amount') }}</th><th class="text-end">{{ __('Paid or credited') }}</th><th class="text-end">{{ __('Written off') }}</th><th class="text-end">{{ __('Still owed') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @foreach($items as $i)<tr><td>{{ $i->document_number ?: __(\Illuminate\Support\Str::headline($i->source_type)).' #'.$i->source_id }}</td><td>{{ $i->document_date->format('d M Y') }}</td><td>{{ $i->due_date?->format('d M Y') }}</td><td class="text-end">{{ $m($i->amount) }}</td><td class="text-end">{{ $m($i->settled) }}</td><td class="text-end">{{ $m($i->written_off) }}</td><td class="text-end fw-bold">{{ $m($svc->outstanding($i)) }}</td><td><x-ui.status-badge :status="$i->status" /></td>
            <td>@can('finance.receivable.writeoff')@if($i->status === 'open')<form method="POST" action="{{ route('admin.finance.receivables.writeoff', $i->public_id) }}" class="d-flex gap-1">@csrf<input name="amount" class="form-control form-control-sm" style="width:6rem" placeholder="{{ __('Amount') }}" required aria-label="{{ __('Amount') }}"><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-sm btn-outline-danger">{{ __('Ask to write off') }}</button></form>@endif @endcan</td></tr>@endforeach</tbody></table></div></div>
@stop
