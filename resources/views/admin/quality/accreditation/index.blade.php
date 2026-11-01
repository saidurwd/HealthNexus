@extends('layouts.adminlte')

@section('page_title', __('Accreditation checklists'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="alert alert-info small">{{ __('A checklist is the hospital\'s own list of requirements for a licence or an accreditation. The starter list here is only an example: replace it with the requirements you are measured against.') }}</div>
    <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Assessments') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Assessment') }}</th><th>{{ __('Checklist') }}</th><th>{{ __('Started') }}</th><th class="text-end">{{ __('Score') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($assessments as $a)<tr><td><a href="{{ route('admin.quality.accreditation.show', $a->public_id) }}">{{ $a->name }}</a></td><td>{{ $a->standard->name }}</td><td>{{ $a->started_on->format('d M Y') }}</td><td class="text-end">{{ $a->score !== null ? (float) $a->score.'%' : '—' }}</td><td><x-ui.status-badge :status="$a->status" /></td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('No assessments yet.') }}</td></tr>@endforelse</tbody></table></div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.quality.accreditation.start') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Start an assessment') }}</h3></div><div class="card-body row g-2">@csrf
        <div class="col-md-5"><select name="standard_id" class="form-select form-select-sm">@foreach($standards as $s)<option value="{{ $s->id }}">{{ $s->name }} ({{ $s->items_count }})</option>@endforeach</select></div><div class="col-md-5"><input name="name" class="form-control form-control-sm" placeholder="{{ __('Name (for example: Annual self-assessment)') }}"></div><div class="col-md-2"><button class="btn btn-sm btn-primary w-100">{{ __('Start') }}</button></div></div></form>@endif
@stop
