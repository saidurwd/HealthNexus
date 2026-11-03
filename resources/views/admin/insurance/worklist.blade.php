@extends('layouts.adminlte')

@section('page_title', __('Denials and rejections to work'))

@section('page_content')
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Reason') }}</th><th>{{ __('Kind') }}</th><th class="text-end">{{ __('Claims') }}</th><th class="text-end">{{ __('Amount') }}</th><th>{{ __('What to do') }}</th></tr></thead><tbody>
        @forelse($worklist as $w)<tr><td><strong>{{ $w['code'] }}</strong></td><td>{{ __($w['label']) }}</td><td>{{ __(\Illuminate\Support\Str::headline($w['kind'])) }}</td><td class="text-end">{{ $w['count'] }}</td><td class="text-end">{{ $w['amount'] }}</td><td class="small">{{ __($w['fix']) }}</td></tr>
        @empty<tr><td colspan="6" class="text-muted">{{ __('Nothing to work: no denied or rejected claims.') }}</td></tr>@endforelse
        </tbody></table></div></div>
@stop
