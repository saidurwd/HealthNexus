@extends('layouts.adminlte')

@section('page_title', 'ICU Episodes')

@section('page_content')
    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Episode</th><th>Patient</th><th>Bed</th><th>Status</th><th>Admitted</th><th></th></tr></thead>
                <tbody>
                    @forelse($episodes as $episode)
                        <tr>
                            <td>{{ $episode->episode_number }}</td>
                            <td>{{ $episode->patient?->full_name }}</td>
                            <td>{{ $episode->icuBed?->bed_code ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucwords(str_replace('_', ' ', $episode->status)) }}</span></td>
                            <td>{{ $episode->admitted_at?->format('Y-m-d H:i') }}</td>
                            <td><a href="{{ route('admin.icu.episodes.show', $episode) }}" class="btn btn-xs btn-info">Open</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No ICU episodes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $episodes->links() }}
@stop
