@extends('layouts.adminlte')

@section('page_title', __('Review of deaths'))

@section('page_content')
    @include('admin.quality._flash')
    <div class="d-flex flex-wrap gap-2 mb-3">@foreach(['pending' => 'To review', 'reviewed' => 'Reviewed', 'discussed' => 'Discussed', 'all' => 'All'] as $k => $l)<a class="btn btn-sm {{ $status === $k ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.quality.mortality', ['status' => $k]) }}">{{ __($l) }}</a>@endforeach
        @if($canReview)<form method="POST" action="{{ route('admin.quality.mortality.find') }}" class="ms-auto">@csrf<button class="btn btn-sm btn-warning">{{ __('Look for new deaths') }}</button></form>@endif</div>
    <p class="small text-muted">{{ __('Every death is reviewed once, within :d days, to learn from it. The review is about the care system, not about a person.', ['d' => $days]) }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Died') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Where') }}</th><th>{{ __('Reviewer') }}</th><th>{{ __('Judged') }}</th><th>{{ __('Status') }}</th></tr></thead><tbody>
        @forelse($reviews as $r)<tr><td><a href="{{ route('admin.quality.mortality.show', $r->public_id) }}">{{ $r->number }}</a></td><td>{{ $r->died_at->setTimezone('Asia/Dhaka')->format('d M Y H:i') }} @if($r->status === 'pending' && $r->died_at->lt(now()->subDays($days)))<x-ui.status-badge status="overdue" />@endif</td><td>{{ $r->patient?->enterprise_patient_no }}</td>
            <td>{{ $r->source_type === 'ed_visit' ? __('Emergency') : __('Ward') }}</td><td>{{ $r->reviewer?->name ?? '—' }}</td><td>{{ $r->classification ? __(\Illuminate\Support\Str::headline($r->classification)) : '—' }}</td><td><x-ui.status-badge :status="$r->status" /></td></tr>
        @empty<tr><td colspan="7" class="text-muted">{{ __('Nothing here.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $reviews->links() }}</div></div>
@stop
