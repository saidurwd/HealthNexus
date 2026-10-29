@extends('layouts.adminlte')

@section('page_title', __('Data quality'))

@section('page_content')
    @include('admin.analytics._flash')
    <div class="d-flex gap-2 mb-3">@foreach(['open' => 'Open', 'resolved' => 'Cleared', 'ignored' => 'Set aside', 'all' => 'All'] as $k => $l)<a class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.analytics.quality', ['status' => $k]) }}">{{ __($l) }}</a>@endforeach
        @can('analytics.quality.manage')<form method="POST" action="{{ route('admin.analytics.quality.run') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-warning">{{ __('Check now') }}</button></form>@endcan</div>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Check') }}</th><th>{{ __('How serious') }}</th><th>{{ __('What is wrong') }}</th><th class="text-end">{{ __('Count') }}</th><th>{{ __('Since') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead><tbody>
        @forelse($issues as $i)<tr><td>{{ __($rules->firstWhere('code', $i->rule_code)->name ?? $i->rule_code) }}</td><td><x-ui.status-badge :status="$i->severity === 'error' ? 'failed' : 'pending'" /> {{ __(\Illuminate\Support\Str::headline($i->severity)) }}</td><td class="small">{{ $i->detail }}</td><td class="text-end">{{ $i->failing }}</td><td>{{ $i->first_seen_at?->setTimezone('Asia/Dhaka')->format('d M Y') }}</td><td>{{ __(\Illuminate\Support\Str::headline($i->status)) }} <small class="text-muted">{{ $i->note }}</small></td>
            <td>@can('analytics.quality.manage')@if($i->status === 'open')<form method="POST" action="{{ route('admin.analytics.quality.aside', $i->id) }}" class="d-flex gap-1">@csrf<input name="note" class="form-control form-control-sm" placeholder="{{ __('Why set aside?') }}" required aria-label="{{ __('Reason') }}"><button class="btn btn-sm btn-outline-secondary">{{ __('Set aside') }}</button></form>@endif @endcan</td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('No problems found.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $issues->links() }}</div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('What is checked') }}</h3></div><ul class="list-group list-group-flush small">@foreach($rules as $r)<li class="list-group-item"><strong>{{ __($r->name) }}</strong> — {{ __($r->description) }}</li>@endforeach</ul></div>
@stop
