{{-- A from/to filter with an optional CSV download. Pass $from, $to; $extra = more inputs as html; $export = true to show the download link. --}}
<form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label" for="from">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-auto"><label class="form-label" for="to">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ $to }}" class="form-control"></div>
    {!! $extra ?? '' !!}
    <div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button></div>
    @if(!empty($export) && auth()->user()->can('finance.report.export'))<div class="col-auto"><a class="btn btn-outline-secondary" href="{{ request()->fullUrlWithQuery(['export' => 'csv']) }}"><i class="bi bi-download"></i> {{ __('Download') }}</a></div>@endif
</div></form>
