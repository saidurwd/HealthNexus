@extends('layouts.adminlte')

@section('page_title', 'Nursing Episodes')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Ward Patients') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['active','transferred','completed','cancelled'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst($status) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Admission #') }}</th><th>{{ __('Ward / Bed') }}</th><th>{{ __('Primary Nurse') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($episodes as $episode)
                        <tr>
                            <td>{{ $episode->patient?->full_name }}</td>
                            <td>{{ $episode->admission?->admission_number }}</td>
                            <td>{{ $episode->admission?->currentAllocation?->bed?->room?->ward?->name ?? '—' }} / {{ $episode->admission?->currentAllocation?->bed?->bed_code ?? '—' }}</td>
                            <td>{{ $episode->primaryNurse?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst($episode->status) }}</span></td>
                            <td><a href="{{ route('admin.nursing.episodes.show', $episode) }}" class="btn btn-xs btn-info">{{ __('Open') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No nursing episodes found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $episodes->links() }}</div>
    </div>
@stop
