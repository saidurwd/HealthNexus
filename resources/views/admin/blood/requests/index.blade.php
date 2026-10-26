@extends('layouts.adminlte')

@section('page_title', __('Blood requests'))

@section('page_content')
    @include('admin.blood._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">
        @foreach([null => 'All', 'requested' => 'Waiting for sample', 'sample_received' => 'Sample received', 'ready' => 'Ready', 'issued' => 'Issued', 'completed' => 'Completed'] as $key => $label)
            <a href="{{ route('admin.blood.requests.index', $key ? ['status' => $key] : []) }}" class="btn btn-sm {{ ($status ?? null) === $key ? 'btn-dark' : 'btn-outline-secondary' }}">{{ __($label) }}</a>
        @endforeach
        @can('bloodbank.request.create')<a href="{{ route('admin.blood.requests.create') }}" class="btn btn-danger ms-auto">{{ __('New request') }}</a>@endcan
    </div>
    @can('bloodbank.mtp.activate')
    <form method="POST" action="{{ route('admin.blood.requests.mtp') }}" class="card mb-3 border-danger" onsubmit="return confirm('{{ __('Start the massive transfusion protocol? Emergency units are released at once.') }}')">@csrf
        <div class="card-body d-flex flex-wrap gap-2 align-items-center"><strong class="text-danger">{{ __('Massive transfusion protocol') }}</strong>
            <input name="patient" class="form-control w-auto" placeholder="{{ __('Patient number') }}" required><input name="authorised_by" class="form-control w-auto" placeholder="{{ __('Authorising doctor') }}" required><input name="reason" class="form-control w-auto flex-grow-1" placeholder="{{ __('Reason') }}" required maxlength="300"><button class="btn btn-danger">{{ __('Start') }}</button></div></form>
    @endcan
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Request') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Needs') }}</th><th>{{ __('Priority') }}</th><th>{{ __('Status') }}</th><th>{{ __('From') }}</th><th>{{ __('Asked') }}</th></tr></thead><tbody>
        @forelse($requests as $r)
            <tr><td><a href="{{ route('admin.blood.requests.show', $r) }}">{{ $r->request_number }}</a></td><td>{{ $r->patient?->full_name }} <small class="text-muted">{{ $r->patient?->enterprise_patient_no }}</small></td><td>{{ $r->units_requested }} × {{ __(config("blood.components.{$r->component}.label")) }}</td>
                <td><span class="badge text-bg-{{ in_array($r->priority, ['emergency', 'massive']) ? 'danger' : ($r->priority === 'urgent' ? 'warning' : 'secondary') }}">{{ __(\Illuminate\Support\Str::headline($r->priority)) }}</span></td><td><x-ui.status-badge :status="$r->status" /></td><td>{{ strtoupper($r->source_module) }}</td><td>{{ $r->requested_at->format('d M H:i') }}</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No requests.') }}</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $requests->links() }}</div></div>
@stop
