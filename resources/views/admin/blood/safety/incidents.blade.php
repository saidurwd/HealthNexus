@extends('layouts.adminlte')

@section('page_title', __('Blood bank incidents'))

@section('page_content')
    @include('admin.blood._flash')
    <div class="card"><ul class="list-group list-group-flush">
        @forelse($incidents as $i)
            <li class="list-group-item"><div class="d-flex justify-content-between"><span><strong>{{ __(\Illuminate\Support\Str::headline($i->incident_type)) }}</strong> <span class="badge text-bg-{{ in_array($i->severity, ['high', 'critical']) ? 'danger' : 'secondary' }}">{{ __(\Illuminate\Support\Str::headline($i->severity)) }}</span> <x-ui.status-badge :status="$i->status" /></span><small class="text-muted">{{ $i->occurred_at->format('d M H:i') }}</small></div>
                <div>{{ $i->description }}</div>
                @if($i->status === 'closed')<div class="text-muted small">{{ __('Resolution') }}: {{ $i->resolution }}</div>
                @else @can('bloodbank.incident.manage')<form method="POST" action="{{ route('admin.blood.incidents.close', $i) }}" class="d-flex gap-2 mt-2">@csrf<input name="resolution" class="form-control" placeholder="{{ __('What was found and done') }}" required maxlength="1000"><button class="btn btn-sm btn-outline-success">{{ __('Close') }}</button></form>@endcan @endif</li>
        @empty<li class="list-group-item text-muted">{{ __('No incidents.') }}</li>@endforelse
    </ul><div class="card-footer">{{ $incidents->links() }}</div></div>
@stop
