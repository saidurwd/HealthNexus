@extends('layouts.adminlte')

@section('page_title', 'Radiology Order — '.$order->order_number)

@section('page_content')
    @if($order->patient)<x-patient-banner :patient="$order->patient" />@endif
    <div class="row">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">{{ $order->order_number }}</h3>
                    <div class="card-tools"><x-ui.status-badge :status="$order->status" /></div>
                </div>
                <div class="card-body">
                    <table class="table table-sm">
                        <tr><th>{{ __('Patient') }}</th><td>{{ $order->patient?->full_name }} ({{ $order->patient?->enterprise_patient_no ?? $order->patient_id }})</td></tr>
                        <tr><th>{{ __('Accession #') }}</th><td>{{ $order->accession_number }}</td></tr>
                        <tr><th>{{ __('Encounter') }}</th><td>{{ $order->encounter?->encounter_no }}</td></tr>
                        <tr><th>{{ __('Priority') }}</th><td>{{ ucfirst($order->priority) }}</td></tr>
                        <tr><th>{{ __('Ordered At') }}</th><td>{{ $order->ordered_at?->format('Y-m-d H:i') }}</td></tr>
                        <tr><th>{{ __('Clinical Indication') }}</th><td>{{ $order->clinical_indication }}</td></tr>
                    </table>
                </div>
            </div>

            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Requested Procedures') }}</h3></div>
                <div class="card-body p-0">
                    <table class="table table-striped">
                        <thead><tr><th>{{ __('Procedure') }}</th><th>{{ __('Status') }}</th><th>{{ __('Examination') }}</th><th>{{ __('Actions') }}</th></tr></thead>
                        <tbody>
                            @forelse($order->items as $item)
                                <tr>
                                    <td>
                                        {{ $item->procedure?->name ?? $item->requested_procedure_name }}
                                        @if($item->isUnmatched())
                                            <span class="badge bg-warning">{{ __('Unmatched') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                                    <td>
                                        @if($item->examination)
                                            <a href="{{ route('admin.radiology.examinations.show', $item->examination) }}">{{ $item->examination->modality?->name }} — {{ $item->examination->scheduled_at?->format('Y-m-d H:i') }}</a>
                                        @endif
                                    </td>
                                    <td>
                                        @if($item->isUnmatched())
                                            @can('radiology.order.view')
                                                <form method="post" action="{{ route('admin.radiology.orders.resolve-item', $order) }}" class="d-flex gap-1">
                                                    @csrf
                                                    <input type="hidden" name="item_id" value="{{ $item->id }}">
                                                    <select name="procedure_id" class="form-control form-control-sm" required>
                                                        <option value="">{{ __('Match to procedure...') }}</option>
                                                        @foreach($availableProcedures as $procedure)
                                                            <option value="{{ $procedure->id }}">{{ $procedure->code }} — {{ $procedure->name }}</option>
                                                        @endforeach
                                                    </select>
                                                    <button type="submit" class="btn btn-xs btn-primary">{{ __('Match') }}</button>
                                                </form>
                                            @endcan
                                        @elseif(! $item->examination && $item->status === 'pending')
                                            @can('radiology.schedule.view')
                                                <a href="{{ route('admin.radiology.scheduling.create', $item) }}" class="btn btn-xs btn-primary">{{ __('Schedule') }}</a>
                                            @endcan
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="4" class="text-center">{{ __('No items.') }}</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="col-md-4">
            <div class="card">
                <div class="card-header"><h3 class="card-title">{{ __('Actions') }}</h3></div>
                <div class="card-body">
                    @if(!in_array($order->status, ['reported', 'cancelled', 'no_show']))
                        @can('radiology.order.cancel')
                            <form method="post" action="{{ route('admin.radiology.orders.cancel', $order) }}" onsubmit="return confirm('Cancel this radiology order?');">
                                @csrf
                                <input type="text" name="reason" class="form-control mb-2" placeholder="{{ __('Cancellation reason') }}" required>
                                <button type="submit" class="btn btn-danger w-100">{{ __('Cancel Order') }}</button>
                            </form>
                        @endcan
                    @endif
                </div>
            </div>
        </div>
    </div>
@stop
