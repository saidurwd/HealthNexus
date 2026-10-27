@extends('layouts.adminlte')

@section('page_title', __('Corporate clients'))

@section('page_content')
    @include('admin.insurance._flash')
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0">
        <thead><tr><th>{{ __('Client') }}</th><th class="text-end">{{ __('Credit limit') }}</th><th class="text-end">{{ __('Owes') }}</th><th class="text-end">{{ __('Can still be billed') }}</th><th>{{ __('Make a statement') }}</th></tr></thead><tbody>
        @forelse($clients as $row)
            <tr><td>{{ $row['client']->name }}</td><td class="text-end">{{ $row['position']['limit'] ?? __('none') }}</td><td class="text-end">{{ $row['position']['owed'] }}</td><td class="text-end {{ $row['position']['available'] !== null && bccomp($row['position']['available'], '0', 2) <= 0 ? 'text-danger' : '' }}">{{ $row['position']['available'] ?? '—' }}</td>
                <td>@can('insurance.corporate.manage')<form method="POST" action="{{ route('admin.insurance.corporate.batches.store') }}" class="d-flex gap-1">@csrf<input type="hidden" name="corporate" value="{{ $row['client']->id }}"><input type="date" name="period_from" class="form-control form-control-sm" value="{{ now()->startOfMonth()->toDateString() }}"><input type="date" name="period_to" class="form-control form-control-sm" value="{{ now()->toDateString() }}"><button class="btn btn-sm btn-outline-primary">{{ __('Make') }}</button></form>@endcan</td></tr>
        @empty<tr><td colspan="5" class="text-muted">{{ __('No corporate clients.') }}</td></tr>@endforelse
        </tbody></table></div></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ __('Statements') }}</h3></div><ul class="list-group list-group-flush small">
        @forelse($batches as $b)<li class="list-group-item d-flex justify-content-between align-items-center"><span>{{ $b->batch_number }} · {{ $names[$b->corporate_id] ?? '' }} · {{ $b->period_from->format('d M') }}–{{ $b->period_to->format('d M Y') }} · {{ $b->invoice_count }} {{ __('invoices') }} · {{ $b->total_amount }} ({{ __('paid') }} {{ $b->paid_amount }}) <x-ui.status-badge :status="$b->status" /> @if($b->due_on)· {{ __('due') }} {{ $b->due_on->format('d M') }}@endif</span>
            @can('insurance.corporate.manage')<span class="d-flex gap-1">@if($b->status === 'open')<form method="POST" action="{{ route('admin.insurance.corporate.batches.issue', $b) }}">@csrf<button class="btn btn-sm btn-outline-success">{{ __('Issue') }}</button></form>@endif
                @if($b->status === 'issued')<form method="POST" action="{{ route('admin.insurance.corporate.batches.pay', $b) }}" class="d-flex gap-1">@csrf<input type="number" step="0.01" name="amount" class="form-control form-control-sm" style="width:110px" required><input name="reference" class="form-control form-control-sm" placeholder="{{ __('Reference') }}"><button class="btn btn-sm btn-outline-primary">{{ __('Receive payment') }}</button></form>@endif</span>@endcan</li>
        @empty<li class="list-group-item text-muted">{{ __('No statements yet.') }}</li>@endforelse</ul></div>
@stop
