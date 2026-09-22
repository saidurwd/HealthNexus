@extends('layouts.adminlte')

@section('page_title', $priceList->name)

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ $priceList->name }} ({{ $priceList->code }})</h3>
            <div class="card-tools">
                @can('billing.pricing.manage')
                    <a href="{{ route('admin.billing.price-lists.edit', $priceList) }}" class="btn btn-warning btn-sm">Edit</a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <table class="table table-sm">
                <tr><th>Currency</th><td>{{ $priceList->currency }}</td></tr>
                <tr><th>Priority</th><td>{{ $priceList->priority }}</td></tr>
                <tr><th>Status</th><td>{{ ucfirst($priceList->status) }}</td></tr>
                <tr><th>Effective</th><td>{{ $priceList->effective_from?->format('Y-m-d') ?? '-' }} &ndash; {{ $priceList->effective_to?->format('Y-m-d') ?? 'ongoing' }}</td></tr>
            </table>
        </div>
    </div>

    <div class="card">
        <div class="card-header"><h3 class="card-title">Price Entries</h3></div>
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Item</th>
                        <th>Scope</th>
                        <th>Unit Price</th>
                        <th>Discount</th>
                        <th>Priority</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($priceList->items as $pli)
                        <tr>
                            <td>{{ $pli->billingItem?->name }}</td>
                            <td class="small">
                                @if($pli->insurancePolicy) Insurance @endif
                                @if($pli->corporate) Corporate: {{ $pli->corporate->name }} @endif
                                @if($pli->provider) Provider: {{ $pli->provider->name }} @endif
                                @if($pli->department) Dept: {{ $pli->department->name }} @endif
                                @if($pli->patient_category) Category: {{ $pli->patient_category }} @endif
                                @if(!$pli->insurance_policy_id && !$pli->corporate_id && !$pli->provider_id && !$pli->department_id && !$pli->patient_category) Default @endif
                            </td>
                            <td>{{ number_format($pli->unit_price, 2) }}</td>
                            <td>{{ $pli->discount_type !== 'none' ? "{$pli->discount_type}: {$pli->discount_value}" : '-' }}</td>
                            <td>{{ $pli->priority }}</td>
                            <td>{{ $pli->is_active ? 'Active' : 'Inactive' }}</td>
                            <td>
                                @can('billing.pricing.manage')
                                    @if($pli->is_active)
                                        <form action="{{ route('admin.billing.price-lists.items.destroy', [$priceList, $pli]) }}" method="post" class="d-inline" onsubmit="return confirm('Deactivate this price?')">
                                            @csrf
                                            @method('delete')
                                            <button class="btn btn-xs btn-danger">Deactivate</button>
                                        </form>
                                    @endif
                                @endcan
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">No price entries yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @can('billing.pricing.manage')
            <div class="card-footer">
                <button class="btn btn-sm btn-primary" type="button" data-bs-toggle="collapse" data-bs-target="#addPriceForm">Add Price</button>
                <div class="collapse mt-3" id="addPriceForm">
                    <form action="{{ route('admin.billing.price-lists.items.store', $priceList) }}" method="post">
                        @csrf
                        <div class="row">
                            <div class="col-md-4 mb-2">
                                <label class="form-label">Item</label>
                                <select name="billing_item_id" class="form-control" required>
                                    <option value="">Select Item</option>
                                    @foreach($billingItems as $bi)
                                        <option value="{{ $bi->id }}">{{ $bi->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label">Unit Price</label>
                                <input type="number" step="0.01" min="0" name="unit_price" class="form-control" required>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label">Discount Type</label>
                                <select name="discount_type" class="form-control">
                                    <option value="none">None</option>
                                    <option value="percentage">Percentage</option>
                                    <option value="fixed">Fixed</option>
                                </select>
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label">Discount Value</label>
                                <input type="number" step="0.01" min="0" name="discount_value" value="0" class="form-control">
                            </div>
                            <div class="col-md-2 mb-2">
                                <label class="form-label">Priority</label>
                                <input type="number" name="priority" value="100" class="form-control" required>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Department (optional)</label>
                                <input type="number" name="department_id" class="form-control" placeholder="Department ID">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Provider (optional)</label>
                                <input type="number" name="provider_id" class="form-control" placeholder="User ID">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Corporate (optional)</label>
                                <input type="number" name="corporate_id" class="form-control" placeholder="Corporate ID">
                            </div>
                            <div class="col-md-3 mb-2">
                                <label class="form-label">Patient Category (optional)</label>
                                <input type="text" name="patient_category" class="form-control">
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary mt-2">Add Price</button>
                    </form>
                </div>
            </div>
        @endcan
    </div>
@stop
