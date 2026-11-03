@extends('layouts.adminlte')

@section('page_title', __($s->dashboard_name))

@section('page_content')
    <div class="alert alert-light border small">{{ __('Kept on :t. These figures do not change.', ['t' => $s->created_at->setTimezone('Asia/Dhaka')->format('d M Y, H:i')]) }} {{ $s->period_from->format('d M Y') }} – {{ $s->period_to->format('d M Y') }}
        <button type="button" class="btn btn-sm btn-outline-secondary float-end d-print-none" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print (A4)') }}</button></div>
    <div class="row g-3">
        @foreach($widgets as $w)
            <div class="{{ $w['type'] === 'kpi' ? 'col-6 col-md-4 col-xl-3' : 'col-lg-6' }}">@include('admin.analytics._widget', ['w' => $w])</div>
        @endforeach
    </div>
@stop
