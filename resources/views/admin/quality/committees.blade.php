@extends('layouts.adminlte')

@section('page_title', __('Committees and meetings'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="row g-3">
        <div class="col-lg-4"><div class="card"><div class="card-header"><h3 class="card-title">{{ __('Committees') }}</h3></div><ul class="list-group list-group-flush small">@foreach($committees as $c)<li class="list-group-item"><strong>{{ $c->name }}</strong><div class="text-muted">{{ $c->purpose }}</div></li>@endforeach</ul></div></div>
        <div class="col-lg-8"><div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Meetings') }}</h3></div><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Meeting') }}</th><th>{{ __('Committee') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
            @forelse($meetings as $m)<tr><td>{{ $m->scheduled_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td><a href="{{ route('admin.quality.meetings.show', $m->public_id) }}">{{ $m->title }}</a></td><td>{{ $m->committee->name }}</td><td><x-ui.status-badge :status="$m->status" /></td></tr>@empty<tr><td colspan="4" class="text-muted">{{ __('No meetings yet.') }}</td></tr>@endforelse</tbody></table></div></div>
            @if($canManage)<form method="POST" action="{{ route('admin.quality.meetings.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Schedule a meeting') }}</h3></div><div class="card-body row g-2">@csrf
                <div class="col-md-5"><select name="committee_id" class="form-select form-select-sm">@foreach($committees as $c)<option value="{{ $c->id }}">{{ $c->name }}</option>@endforeach</select></div><div class="col-md-4"><input name="title" class="form-control form-control-sm" placeholder="{{ __('Title') }}" required></div><div class="col-md-3"><input type="datetime-local" name="scheduled_at" class="form-control form-control-sm" required></div>
                <div class="col-md-8"><input name="agenda" class="form-control form-control-sm" placeholder="{{ __('Agenda') }}"></div><div class="col-md-2"><input name="location" class="form-control form-control-sm" placeholder="{{ __('Place') }}"></div><div class="col-md-2"><button class="btn btn-sm btn-primary w-100">{{ __('Schedule') }}</button></div></div></form>@endif</div>
    </div>
@stop
