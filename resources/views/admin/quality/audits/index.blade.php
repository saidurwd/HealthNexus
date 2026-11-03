@extends('layouts.adminlte')

@section('page_title', __('Clinical audits'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Audit') }}</th><th>{{ __('Date') }}</th><th class="text-end">{{ __('Sample') }}</th><th class="text-end">{{ __('Criteria met') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($audits as $a)<tr><td><a href="{{ route('admin.quality.audits.show', $a->public_id) }}">{{ $a->number }}</a></td><td>{{ $a->title }}</td><td>{{ $a->audited_on->format('d M Y') }}</td><td class="text-end">{{ $a->sample_size }}</td><td class="text-end">{{ $a->compliance !== null ? (float) $a->compliance.'%' : '—' }}</td><td><x-ui.status-badge :status="$a->status" /></td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No audits yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $audits->links() }}</div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.quality.audits.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Start an audit') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-5"><input name="title" class="form-control form-control-sm" placeholder="{{ __('What is being audited') }}" required></div><div class="col-md-3"><select name="department_id" class="form-select form-select-sm"><option value="">{{ __('Department') }}</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-md-2"><input type="date" name="audited_on" class="form-control form-control-sm" value="{{ today()->toDateString() }}"></div><div class="col-md-2"><input type="number" name="sample_size" class="form-control form-control-sm" min="0" placeholder="{{ __('Sample') }}"></div>
        <div class="col-12"><input name="standard" class="form-control form-control-sm" placeholder="{{ __('The standard: what good looks like') }}"></div><div class="col-12"><button class="btn btn-sm btn-primary">{{ __('Start') }}</button></div></div></form>@endif
@stop
