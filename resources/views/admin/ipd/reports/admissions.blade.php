@extends('layouts.adminlte')

@section('page_title', 'Admission Report')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Admissions ({{ $from }} to {{ $to }})</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3"><input type="date" name="from" value="{{ $from }}" class="form-control"></div>
                <div class="col-md-3"><input type="date" name="to" value="{{ $to }}" class="form-control"></div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Admission #</th><th>Patient</th><th>Type</th><th>Department</th><th>Attending</th><th>Admitted</th></tr></thead>
                <tbody>
                    @forelse($admissions as $admission)
                        <tr>
                            <td>{{ $admission->admission_number }}</td>
                            <td>{{ $admission->patient?->full_name }}</td>
                            <td>{{ $admission->admissionType?->name ?? '—' }}</td>
                            <td>{{ $admission->department?->name ?? '—' }}</td>
                            <td>{{ $admission->attendingProvider?->name ?? '—' }}</td>
                            <td>{{ $admission->admitted_at?->format('Y-m-d H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No admissions in this range.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $admissions->links() }}</div>
    </div>
@stop
