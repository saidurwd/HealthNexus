@extends('layouts.adminlte')

@section('page_title', __('Payroll :n', ['n' => $run->number]))

@section('page_content')
    @include('admin.hr._flash')
    <div class="card mb-3"><div class="card-body d-flex flex-wrap gap-3 align-items-center small">
        <x-ui.status-badge :status="$run->status" /> <span>{{ $run->period->month_start->format('F Y') }}</span> <span>{{ $run->employee_count }} {{ __('people') }}</span>
        <span>{{ __('Gross') }} <strong>{{ $run->total_gross }}</strong></span><span>{{ __('Deductions') }} {{ $run->total_deductions }}</span><span>{{ __('Tax') }} {{ $run->total_tax }}</span><span>{{ __('Net') }} <strong>{{ $run->total_net }}</strong></span><span>{{ __('Employer cost') }} {{ $run->total_employer }}</span>
        <span class="text-muted" title="{{ __('Same inputs always give the same fingerprint') }}">{{ __('Fingerprint') }} {{ substr((string) $run->input_hash, 0, 10) }} · {{ __('calculated :n time(s)', ['n' => $run->calculation]) }}</span>
        @if($run->reason)<span>{{ $run->reason }}</span>@endif
        <form method="POST" action="{{ route('admin.hr.payroll.move', $run) }}" class="ms-auto d-flex flex-wrap gap-2">@csrf
            @if(in_array($run->status, ['draft', 'calculated']))@can('payroll.run.create')<button name="action" value="calculate" class="btn btn-sm btn-outline-primary">{{ __('Calculate again') }}</button>@endcan
                @can('payroll.run.approve')@if($run->status === 'calculated')<button name="action" value="approve" class="btn btn-sm btn-success" onclick="return confirm('{{ __('Approving locks the month. Continue?') }}')">{{ __('Approve and lock') }}</button>@endif @endcan
                @can('payroll.run.create')<input name="reason" class="form-control form-control-sm w-auto" placeholder="{{ __('Why cancel?') }}"><button name="action" value="cancel" class="btn btn-sm btn-outline-danger">{{ __('Cancel run') }}</button>@endcan @endif
            @if(in_array($run->status, ['approved', 'paid']))
                @can('payroll.payslip.publish')@unless($run->payslips_published_at)<button name="action" value="publish" class="btn btn-sm btn-outline-primary">{{ __('Publish payslips') }}</button>@endunless @endcan
                @can('payroll.journal.post')@unless($run->finance_reference)<button name="action" value="journal" class="btn btn-sm btn-outline-secondary">{{ __('Send to accounts') }}</button>@endunless @endcan
                @if($run->status === 'approved')@can('payroll.run.approve')<button name="action" value="paid" class="btn btn-sm btn-outline-success">{{ __('Mark as paid') }}</button>@endcan @endif @endif
        </form>
    </div></div>

    @if($exceptions)<div class="card border-warning mb-3"><div class="card-header"><h3 class="card-title">{{ __('Needs attention') }}</h3></div><ul class="list-group list-group-flush small">
        @foreach($exceptions as $code => $rows)<li class="list-group-item"><strong>{{ __(\Illuminate\Support\Str::headline($code)) }}</strong> ({{ count($rows) }}): {{ collect($rows)->take(8)->map(fn ($r) => $r['employee_no'].' '.$r['employee'])->implode(', ') }}@if(in_array($code, ['no_structure', 'negative_net', 'attendance_unprocessed']))<span class="badge text-bg-danger ms-1">{{ __('stops approval') }}</span>@endif</li>@endforeach</ul></div>@endif

    @if($checks)<div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Does it add up?') }}</h3></div><div class="card-body d-flex flex-wrap gap-3 small">@foreach($checks as $name => $ok)<span class="{{ $ok ? 'text-success' : 'text-danger' }}"><i class="bi bi-{{ $ok ? 'check-circle' : 'x-circle' }}"></i> {{ __(\Illuminate\Support\Str::headline($name)) }}</span>@endforeach</div></div>@endif

    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Person') }}</th><th class="text-end">{{ __('Paid days') }}</th><th class="text-end">{{ __('Gross') }}</th><th class="text-end">{{ __('Deductions') }}</th><th class="text-end">{{ __('Tax') }}</th><th class="text-end">{{ __('Net') }}</th><th></th></tr></thead>
        <tbody>@foreach($lines as $l)<tr class="{{ $l->exceptions ? 'table-warning' : '' }}"><td>{{ $l->employee->employee_no }} {{ $l->employee->fullName() }}</td><td class="text-end">{{ $l->inputs['paid_days'] ?? '—' }}</td><td class="text-end">{{ $l->gross }}</td><td class="text-end">{{ $l->deductions }}</td><td class="text-end">{{ $l->tax }}</td><td class="text-end fw-semibold">{{ $l->net }}</td><td>@can('payroll.payslip.view')<a href="{{ route('admin.hr.payslips.show', $l) }}">{{ __('Payslip') }}</a>@endcan</td></tr>@endforeach</tbody></table></div><div class="card-footer">{{ $lines->links() }}</div></div>

    <div class="row g-3">
        @if(in_array($run->status, ['approved', 'paid']))@can('payroll.payment.file')<div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Salary payment files') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($batches as $b)<li class="list-group-item d-flex justify-content-between"><span>{{ $b->method === 'bank' ? __('Bank advice') : __('Mobile wallet') }} · {{ $b->payee_count }} {{ __('people') }} · {{ $b->total }}</span><a href="{{ route('admin.hr.payment-files.download', $b) }}">{{ __('Download') }}</a></li>@empty<li class="list-group-item text-muted">{{ __('Not prepared yet.') }}</li>@endforelse</ul>
            <form method="POST" action="{{ route('admin.hr.payroll.files', $run) }}" class="card-footer d-flex gap-2">@csrf<select name="method" class="form-select form-select-sm w-auto"><option value="bank">{{ __('Bank') }}</option><option value="mobile_wallet">{{ __('Mobile wallet') }}</option></select><button class="btn btn-sm btn-outline-primary">{{ __('Prepare') }}</button></form></div></div>@endcan @endif
        @can('payroll.adjustment.manage')<div class="col-lg-6"><div class="card h-100"><div class="card-header"><h3 class="card-title">{{ __('Adjustments for this month') }}</h3></div>
            <ul class="list-group list-group-flush small">@forelse($adjustments as $a)<li class="list-group-item d-flex justify-content-between"><span>{{ $a->employee->fullName() }} · {{ $a->name }} ({{ __(\Illuminate\Support\Str::headline($a->kind)) }})</span><span>{{ $a->amount }} {!! $a->run_id ? '<i class="bi bi-lock"></i>' : '' !!}</span></li>@empty<li class="list-group-item text-muted">{{ __('None.') }}</li>@endforelse</ul>
            <form method="POST" action="{{ route('admin.hr.payroll.adjustments', $run) }}" class="card-footer d-flex flex-wrap gap-2">@csrf<select name="employee" class="form-select form-select-sm w-auto">@foreach($employees as $e)<option value="{{ $e->public_id }}">{{ $e->employee_no }} {{ $e->first_name }}</option>@endforeach</select><select name="kind" class="form-select form-select-sm w-auto"><option value="earning">{{ __('Add') }}</option><option value="deduction">{{ __('Deduct') }}</option></select><input name="name" class="form-control form-control-sm w-auto" placeholder="{{ __('What (e.g. festival bonus)') }}" required><input type="number" step="0.01" min="0.01" name="amount" class="form-control form-control-sm" style="width:100px" required><input name="reason" class="form-control form-control-sm" placeholder="{{ __('Why') }}" required><button class="btn btn-sm btn-outline-primary">{{ __('Save') }}</button></form></div></div>@endcan
    </div>
@stop
