@extends('layouts.adminlte')

@section('page_title', 'Bed Availability')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Bed Availability Search</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Search bed code...">
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-control">
                        <option value="">All Statuses</option>
                        @foreach(['available','reserved','occupied','cleaning','blocked','maintenance','isolation'] as $status)
                            <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_', ' ', $status)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="gender" class="form-control">
                        <option value="">Any Gender</option>
                        <option value="male" {{ request('gender') == 'male' ? 'selected' : '' }}>Male</option>
                        <option value="female" {{ request('gender') == 'female' ? 'selected' : '' }}>Female</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <div class="form-check mt-2">
                        <input type="checkbox" name="isolation" value="1" class="form-check-input" id="isolation" {{ request('isolation') ? 'checked' : '' }}>
                        <label class="form-check-label" for="isolation">Isolation only</label>
                    </div>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">Search</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>Bed Code</th><th>Room</th><th>Ward</th><th>Type</th><th>Gender</th><th>Status</th></tr></thead>
                <tbody>
                    @forelse($beds as $bed)
                        <tr>
                            <td>{{ $bed->bed_code }}</td>
                            <td>{{ $bed->room?->room_number }}</td>
                            <td>{{ $bed->room?->ward?->name }}</td>
                            <td>{{ $bed->bedType?->name ?? '—' }}</td>
                            <td>{{ ucfirst($bed->gender_type) }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $bed->status)) }}</span></td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">No beds match the search.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $beds->links() }}</div>
    </div>
@stop
