@extends('layouts.adminlte')

@section('page_title', 'New Transfer')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Stock Transfer</h3></div>
        <form method="POST" action="{{ route('admin.pharmacy.transfers.store') }}">
            @csrf
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Source Store</label>
                        <select name="source_store_id" class="form-control @error('source_store_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @error('source_store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label">Destination Store</label>
                        <select name="destination_store_id" class="form-control @error('destination_store_id') is-invalid @enderror" required>
                            <option value="">Select</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->name }}</option>
                            @endforeach
                        </select>
                        @error('destination_store_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                </div>

                <table class="table table-bordered">
                    <thead><tr><th>Batch</th><th>Transfer?</th><th>Quantity</th></tr></thead>
                    <tbody>
                        @foreach($batches as $batch)
                            <tr>
                                <td>{{ $batch->medication?->name }} — {{ $batch->batch_number }} (exp. {{ $batch->expiry_date?->format('Y-m-d') }})</td>
                                <td>
                                    <input type="checkbox" name="items[{{ $loop->index }}][include]" value="1">
                                    <input type="hidden" name="items[{{ $loop->index }}][medication_id]" value="{{ $batch->medication_id }}">
                                    <input type="hidden" name="items[{{ $loop->index }}][batch_id]" value="{{ $batch->id }}">
                                </td>
                                <td><input type="number" name="items[{{ $loop->index }}][quantity_requested]" min="1" class="form-control form-control-sm"></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.pharmacy.transfers.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Request Transfer</button>
            </div>
        </form>
    </div>

    <script>
        document.querySelector('form').addEventListener('submit', function () {
            document.querySelectorAll('input[name$="[include]"]').forEach(function (checkbox) {
                if (!checkbox.checked) {
                    checkbox.closest('tr').querySelectorAll('input').forEach(function (input) { input.disabled = true; });
                } else {
                    checkbox.disabled = true;
                }
            });
        });
    </script>
@stop
