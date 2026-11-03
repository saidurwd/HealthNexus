@extends('layouts.adminlte')

@section('page_title', __('Cleaning audits'))

@section('page_content')
    @include('admin.facility._flash')
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Area') }}</th><th class="text-end">{{ __('Score') }}</th><th>{{ __('What failed') }}</th><th>{{ __('By') }}</th></tr></thead><tbody>
        @forelse($audits as $a)<tr><td>{{ $a->audited_on->format('d M Y') }}</td><td>{{ $a->area }}</td><td class="text-end">{{ (float) $a->score }}% @if((float) $a->score < $pass)<x-ui.status-badge status="non_compliant" />@endif</td><td class="small">{{ implode(', ', $a->findings ?? []) }}</td><td>{{ $a->auditor?->name }}</td></tr>@empty<tr><td colspan="5" class="text-muted">{{ __('No audits yet.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $audits->links() }}</div></div>
    @if($canManage)<form method="POST" action="{{ route('admin.facility.audits.store') }}" class="card"><div class="card-header"><h3 class="card-title">{{ __('Audit an area') }}</h3></div><div class="card-body">@csrf
        <input name="area" class="form-control form-control-sm mb-2" placeholder="{{ __('Area (for example: Ward 3)') }}" required>
        @foreach($items as $i => $item)<div class="form-check"><input type="hidden" name="items[{{ $i }}]" value="{{ $item }}"><input class="form-check-input" type="checkbox" name="ok[]" value="{{ $i }}" id="ok{{ $i }}"><label class="form-check-label small" for="ok{{ $i }}">{{ __($item) }}</label></div>@endforeach
        <p class="small text-muted mt-1">{{ __('Tick what was clean. A score under :p% raises a warning.', ['p' => $pass]) }}</p><button class="btn btn-sm btn-primary">{{ __('Save the audit') }}</button></div></form>@endif
@stop
