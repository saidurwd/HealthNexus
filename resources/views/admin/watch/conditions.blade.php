@extends('layouts.adminlte')

@section('page_title', __('Conditions watched'))

@section('page_content')
    @include('admin.watch._flash')
    <div class="alert alert-warning small">{{ __('This list and these numbers are examples. Which diseases must be reported, and the codes and limits, are decided by the health authority: check them with the civil surgeon\'s office before relying on them.') }}</div>
    @if($canManage)<form method="POST" action="{{ route('admin.watch.conditions.install') }}" class="mb-3">@csrf<button class="btn btn-sm btn-outline-secondary">{{ __('Add the starting list') }}</button></form>@endif
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0 align-middle">
        <thead><tr><th>{{ __('Condition') }}</th><th>{{ __('Counts diagnoses starting') }}</th><th>{{ __('Window (days)') }}</th><th>{{ __('Fewest patients') }}</th><th>{{ __('Times the usual') }}</th><th>{{ __('Cluster in a district') }}</th><th>{{ __('Seriousness') }}</th><th>{{ __('On') }}</th>@if($canManage)<th></th>@endif</tr></thead><tbody>
        @forelse($rows as $c)
            @if($canManage)<form method="POST" action="{{ route('admin.watch.conditions.update', $c->id) }}" id="c{{ $c->id }}">@csrf</form>@endif
            <tr><td>{{ app()->getLocale() === 'bn' && $c->name_bn ? $c->name_bn : $c->name }}</td><td class="text-muted small">{{ implode(', ', $c->icd_prefixes) }}</td>
            @if($canManage)
                <td><input form="c{{ $c->id }}" type="number" name="window_days" value="{{ $c->window_days }}" class="form-control form-control-sm" style="width:5rem" aria-label="{{ __('Window (days)') }}"></td>
                <td><input form="c{{ $c->id }}" type="number" name="min_cases" value="{{ $c->min_cases }}" class="form-control form-control-sm" style="width:5rem" aria-label="{{ __('Fewest patients') }}"></td>
                <td><input form="c{{ $c->id }}" type="number" step="0.1" name="spike_factor" value="{{ $c->spike_factor }}" class="form-control form-control-sm" style="width:5rem" aria-label="{{ __('Times the usual') }}"></td>
                <td><input form="c{{ $c->id }}" type="number" name="cluster_cases" value="{{ $c->cluster_cases }}" class="form-control form-control-sm" style="width:5rem" aria-label="{{ __('Cluster in a district') }}"></td>
                <td><select form="c{{ $c->id }}" name="severity" class="form-select form-select-sm" aria-label="{{ __('Seriousness') }}"><option value="warning" @selected($c->severity === 'warning')>{{ __('Warning') }}</option><option value="critical" @selected($c->severity === 'critical')>{{ __('Critical') }}</option></select></td>
                <td><input form="c{{ $c->id }}" type="hidden" name="active" value="0"><input form="c{{ $c->id }}" type="checkbox" name="active" value="1" @checked($c->active) aria-label="{{ __('On') }}"></td><td><button form="c{{ $c->id }}" class="btn btn-sm btn-primary">{{ __('Save') }}</button></td>
            @else
                <td>{{ $c->window_days }}</td><td>{{ $c->min_cases }}</td><td>{{ $c->spike_factor }}</td><td>{{ $c->cluster_cases }}</td><td>{{ __(\Illuminate\Support\Str::headline($c->severity)) }}</td><td>{{ $c->active ? __('Yes') : __('No') }}</td>
            @endif</tr>
        @empty<tr><td colspan="9"><x-ui.empty-state :title="__('No condition is being watched.')" icon="bi-list-check" /></td></tr>@endforelse
        </tbody></table></div></div>
@stop
