@extends('layouts.adminlte')

@section('page_title', __('Incidents'))

@section('page_content')
    @include('admin.quality._flash')
    <form class="row g-2 mb-3" method="GET">
        <div class="col-md-2"><input name="q" class="form-control form-control-sm" placeholder="{{ __('Number') }}" value="{{ $filters['q'] ?? '' }}"></div>
        <div class="col-md-2"><select name="status" class="form-select form-select-sm"><option value="">{{ __('Any status') }}</option>@foreach(['reported', 'triaged', 'investigating', 'actioned', 'closed'] as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="category" class="form-select form-select-sm"><option value="">{{ __('Any kind of event') }}</option>@foreach($policy->list('incident.categories') as $c)<option value="{{ $c }}" @selected(($filters['category'] ?? '') === $c)>{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><select name="harm" class="form-select form-select-sm"><option value="">{{ __('Any harm') }}</option>@foreach($policy->list('incident.harm') as $h)<option value="{{ $h }}" @selected(($filters['harm'] ?? '') === $h)>{{ __(\Illuminate\Support\Str::headline($h)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><div class="form-check mt-1"><input class="form-check-input" type="checkbox" name="overdue" value="1" id="od" @checked(!empty($filters['overdue']))><label class="form-check-label small" for="od">{{ __('Past due date') }}</label></div></div>
        <div class="col-md-2"><button class="btn btn-sm btn-primary">{{ __('Filter') }}</button></div>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('When') }}</th><th>{{ __('Kind of event') }}</th><th>{{ __('What') }}</th><th>{{ __('Harm') }}</th><th>{{ __('Status') }}</th><th>{{ __('Due') }}</th></tr></thead><tbody>
        @forelse($incidents as $i)<tr><td><a href="{{ route('admin.quality.incidents.show', $i->public_id) }}">{{ $i->number }}</a></td><td>{{ $i->occurred_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ __(\Illuminate\Support\Str::headline($i->effective('category'))) }}</td>
            <td>{{ __(\Illuminate\Support\Str::headline($i->kind)) }}</td><td>{{ __(\Illuminate\Support\Str::headline($i->harmNow())) }}</td><td><x-ui.status-badge :status="$i->status" /></td>
            <td>{{ $i->due_on?->format('d M') }} @if($i->status !== 'closed' && $i->due_on && $i->due_on->lt(today()))<x-ui.status-badge status="overdue" />@endif</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No incidents match.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $incidents->links() }}</div></div>
@stop
