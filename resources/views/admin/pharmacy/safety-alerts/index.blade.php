@extends('layouts.adminlte')

@section('page_title', 'Pharmacy Safety Alerts')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Safety Alerts</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <select name="status" class="form-control">
                        <option value="">All</option>
                        <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Unresolved</option>
                        <option value="overridden" {{ request('status') === 'overridden' ? 'selected' : '' }}>Overridden</option>
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Filter</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Type</th><th>Severity</th><th>Medication</th><th>Explanation</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse($alerts as $alert)
                        <tr>
                            <td>{{ ucfirst(str_replace('_', ' ', $alert->alert_type)) }}</td>
                            <td><span class="badge {{ $alert->severity === 'severe' ? 'bg-danger' : ($alert->severity === 'moderate' ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($alert->severity) }}</span></td>
                            <td>{{ $alert->medication?->name }}</td>
                            <td>{{ $alert->explanation }}</td>
                            <td>
                                @if($alert->is_overridden)
                                    <span class="badge bg-success">Overridden</span>
                                @else
                                    <span class="badge bg-danger">Unresolved</span>
                                @endif
                            </td>
                            <td>
                                @can('override', $alert)
                                    @unless($alert->is_overridden)
                                        <form method="POST" action="{{ route('admin.pharmacy.safety-alerts.override', $alert) }}" class="d-flex gap-1">
                                            @csrf
                                            <input type="text" name="reason" class="form-control form-control-sm" placeholder="Override reason" required>
                                            <button type="submit" class="btn btn-xs btn-warning">Override</button>
                                        </form>
                                    @endunless
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No safety alerts found.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $alerts->links() }}</div>
    </div>
@stop
