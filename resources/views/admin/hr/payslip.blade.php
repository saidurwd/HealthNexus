@extends('layouts.adminlte')

@section('page_title', __('Payslip'))

@section('page_content')
    <div class="card" style="max-width:720px"><div class="card-header d-flex justify-content-between"><h3 class="card-title">{{ $line->run->period->month_start->format('F Y') }} <small class="text-muted">{{ $line->run->number }}</small></h3><a href="{{ $back }}" class="btn btn-sm btn-outline-secondary d-print-none">{{ __('Back') }}</a></div>
        <div class="card-body small"><div class="fw-semibold fs-6">{{ $line->employee->fullName() }} <span class="text-muted">{{ $line->employee->employee_no }}</span></div>
            <div class="text-muted mb-2">{{ __('Paid days') }} {{ $line->inputs['paid_days'] ?? '' }} / {{ $line->inputs['days_in_month'] ?? '' }}</div>
            <div class="row"><div class="col-md-6"><div class="fw-semibold">{{ __('Earnings') }}</div>@foreach($line->items->where('kind', 'earning') as $i)<div class="d-flex justify-content-between"><span>{{ $i->name }}</span><span>{{ $i->amount }}</span></div>@endforeach<div class="d-flex justify-content-between border-top fw-semibold"><span>{{ __('Gross') }}</span><span>{{ $line->gross }}</span></div></div>
                <div class="col-md-6"><div class="fw-semibold">{{ __('Deductions') }}</div>@foreach($line->items->whereIn('kind', ['deduction', 'tax']) as $i)<div class="d-flex justify-content-between"><span>{{ $i->name }}</span><span>{{ $i->amount }}</span></div>@endforeach<div class="d-flex justify-content-between border-top fw-semibold"><span>{{ __('Total') }}</span><span>{{ bcadd((string) $line->deductions, (string) $line->tax, 2) }}</span></div></div></div>
            <div class="d-flex justify-content-between fs-5 border-top mt-3 pt-2"><strong>{{ __('Net pay') }}</strong><strong>{{ $line->net }}</strong></div></div></div>
@stop
