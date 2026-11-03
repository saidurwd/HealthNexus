@extends('layouts.adminlte')

@section('page_title', __('Patient complaints'))

@section('page_content')
    @include('admin.quality._flash')
    <p class="small text-muted">{{ __('Complaints stay with patient relations. Raising one here makes an incident that carries only the complaint number, so the quality team can investigate and act without copying what the patient wrote.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Received') }}</th><th>{{ __('Kind') }}</th><th>{{ __('Seriousness') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($complaints as $c)<tr><td>{{ $c->number }}</td><td>{{ $c->created_at->setTimezone('Asia/Dhaka')->format('d M Y') }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->category)) }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->severity)) }}</td><td><x-ui.status-badge :status="$c->status" /></td>
            <td>@if(isset($linked[$c->id]))<a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.quality.incidents.show', $incidents[$linked[$c->id]] ?? '') }}">{{ __('Open the incident') }}</a>@else<form method="POST" action="{{ route('admin.quality.complaints.raise', $c->public_id) }}" class="d-flex gap-1">@csrf<select name="harm" class="form-select form-select-sm" style="width:130px">@foreach(config('quality.incident.harm') as $h)<option value="{{ $h }}">{{ __(\Illuminate\Support\Str::headline($h)) }}</option>@endforeach</select><button class="btn btn-sm btn-primary">{{ __('Raise as incident') }}</button></form>@endif</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No complaints.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $complaints->links() }}</div></div>
@stop
