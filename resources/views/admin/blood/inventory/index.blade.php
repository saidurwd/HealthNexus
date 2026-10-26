@extends('layouts.adminlte')

@section('page_title', __('Blood stock'))

@section('page_content')
    @include('admin.blood._flash')
    <form method="GET" class="d-flex flex-wrap gap-2 mb-3">
        <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" class="form-control w-auto" placeholder="{{ __('Unit number') }}">
        <select name="component" class="form-select w-auto"><option value="">{{ __('All components') }}</option>@foreach($components as $c)<option value="{{ $c }}" @selected(($filters['component'] ?? '') === $c)>{{ __(config("blood.components.$c.label")) }}</option>@endforeach</select>
        <select name="status" class="form-select w-auto"><option value="">{{ __('Any status') }}</option>@foreach($statuses as $s)<option value="{{ $s }}" @selected(($filters['status'] ?? '') === $s)>{{ __(\Illuminate\Support\Str::headline($s)) }}</option>@endforeach</select>
        <select name="abo" class="form-select w-auto"><option value="">{{ __('Any group') }}</option>@foreach(config('blood.abo') as $g)<option @selected(($filters['abo'] ?? '') === $g)>{{ $g }}</option>@endforeach</select>
        <select name="rh" class="form-select w-auto"><option value="">{{ __('Any Rh') }}</option><option value="positive" @selected(($filters['rh'] ?? '') === 'positive')>{{ __('Positive') }}</option><option value="negative" @selected(($filters['rh'] ?? '') === 'negative')>{{ __('Negative') }}</option></select>
        <button class="btn btn-outline-secondary">{{ __('Filter') }}</button>
        <a href="{{ route('admin.blood.inventory.storage') }}" class="btn btn-outline-primary ms-auto">{{ __('Storage and temperature') }}</a>
    </form>
    <div class="card"><div class="table-responsive"><table class="table table-sm table-hover mb-0">
        <thead><tr><th>{{ __('Unit') }}</th><th>{{ __('Component') }}</th><th>{{ __('Group') }}</th><th>{{ __('Status') }}</th><th>{{ __('Where') }}</th><th>{{ __('Expires') }}</th></tr></thead><tbody>
        @forelse($units as $u)
            <tr><td><a href="{{ route('admin.blood.units.show', $u) }}">{{ $u->unit_number }}</a></td><td>{{ __(config("blood.components.{$u->component}.label")) }}</td><td>{{ $u->abo ? $u->abo.' '.__(\Illuminate\Support\Str::headline($u->rh)) : '—' }}</td>
                <td><x-ui.status-badge :status="$u->status" />@if($u->blocked_reason) <small class="text-danger">{{ __(\Illuminate\Support\Str::headline($u->blocked_reason)) }}</small>@endif</td><td>{{ $u->location?->name ?? '—' }}</td>
                <td class="{{ $u->expires_at->lt(now()->addDays(3)) ? 'text-danger' : '' }}">{{ $u->expires_at->format('d M Y H:i') }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('No units match.') }}</td></tr>@endforelse
        </tbody></table></div><div class="card-footer">{{ $units->links() }}</div></div>
@stop
