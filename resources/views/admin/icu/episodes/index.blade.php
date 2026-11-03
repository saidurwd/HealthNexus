@extends('layouts.adminlte')

@section('page_title', 'ICU Episodes')

@section('page_content')
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Episode') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Bed') }}</th><th>{{ __('Status') }}</th><th>{{ __('Admitted') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($episodes as $episode)
                        <tr>
                            <td>{{ $episode->episode_number }}</td>
                            <td>{{ $episode->patient?->full_name }}</td>
                            <td>{{ $episode->icuBed?->bed_code ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $episode->status)) }}</span></td>
                            <td>{{ $episode->admitted_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.icu.episodes.show', $episode) }}" class="btn btn-xs btn-info">{{ __('Open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No ICU episodes.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $episodes->links() }}
@stop
