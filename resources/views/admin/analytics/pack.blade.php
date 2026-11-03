@extends('layouts.adminlte')

@section('page_title', __('Month-end pack').' · '.$month->format('F Y'))

@section('page_content')
    <style>
        @media print {
            @page { size: A4; margin: 12mm; }
            .app-sidebar, .app-header, .app-footer, .d-print-none { display: none !important; }
            .app-main, .app-wrapper { margin: 0 !important; padding: 0 !important; }
            .pack-sheet { page-break-after: always; break-after: page; }
            .pack-sheet:last-child { page-break-after: auto; break-after: auto; }
        }
    </style>
    <div class="d-print-none mb-3"><button type="button" class="btn btn-sm btn-primary" onclick="window.print()"><i class="bi bi-printer"></i> {{ __('Print (A4)') }}</button>
        <span class="text-muted small ms-2">{{ __('One dashboard to a sheet. Small numbers are hidden.') }}</span></div>
    @foreach($items as $item)
        <section class="pack-sheet mb-4">
            <h2 class="h5">{{ __($item['s']->dashboard_name) }} <span class="text-muted fs-6">· {{ $item['s']->period_from->format('d M Y') }} – {{ $item['s']->period_to->format('d M Y') }}</span></h2>
            <div class="row g-3">
                @foreach($item['widgets'] as $w)
                    <div class="{{ $w['type'] === 'kpi' ? 'col-4' : 'col-6' }}">@include('admin.analytics._widget', ['w' => $w])</div>
                @endforeach
            </div>
        </section>
    @endforeach
@stop
