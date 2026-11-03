@extends('layouts.adminlte')

@section('page_title', 'Critical Findings Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Critical Findings Report ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Finding') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Detected At') }}</th><th>{{ __('Status') }}</th></tr></thead>
                <tbody>
                    @forelse($findings as $finding)
                        <tr>
                            <td>{{ Str::limit($finding->finding_text, 60) }}</td>
                            <td>{{ $finding->report->examination->orderItem->radiologyOrder->patient?->full_name }}</td>
                            <td>{{ $finding->detected_at?->format('Y-m-d H:i') }}</td>
                            <td>{{ ucfirst($finding->status) }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="text-center">{{ __('No critical findings in this period.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $findings->links() }}</div>
    </div>
@stop
