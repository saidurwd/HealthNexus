@extends('layouts.adminlte')

@section('page_title', 'Intake / Output')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Net Balance ({{ $from->format('Y-m-d H:i') }} to {{ $to->format('Y-m-d H:i') }})</h3></div>
        <div class="card-body">
            Intake: <strong>{{ $balance['total_intake'] }}</strong> &nbsp;
            Output: <strong>{{ $balance['total_output'] }}</strong> &nbsp;
            Net: <strong>{{ $balance['net_balance'] }}</strong>
        </div>
    </div>

    @can('nursing.observation.create')
    <div class="card">
        <div class="card-header"><h3 class="card-title">Record Entry</h3></div>
        <form action="{{ route('admin.nursing.intake-output.store', $episode) }}" method="post">
            @csrf
            <div class="card-body row">
                <div class="col-md-3"><select name="type" class="form-control"><option value="intake">Intake</option><option value="output">Output</option></select></div>
                <div class="col-md-3"><input name="category" class="form-control" placeholder="Category (oral, iv, urine...)" required></div>
                <div class="col-md-3"><input type="number" step="0.01" name="amount" class="form-control" placeholder="Amount" required></div>
                <div class="col-md-3"><input name="unit" class="form-control" placeholder="Unit" value="ml"></div>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save</button></div>
        </form>
    </div>
    @endcan

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>Time</th><th>Type</th><th>Category</th><th>Amount</th></tr></thead>
                <tbody>
                    @forelse($records as $r)
                        <tr><td>{{ $r->recorded_at }}</td><td>{{ ucfirst($r->type) }}</td><td>{{ $r->category }}</td><td>{{ $r->amount }} {{ $r->unit }}</td></tr>
                    @empty
                        <tr><td colspan="4" class="text-center">No entries in this period.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@stop
