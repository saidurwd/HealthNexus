<form method="GET" class="card card-body mb-3"><div class="row g-2 align-items-end">
    <div class="col-auto"><label class="form-label" for="from">{{ __('From') }}</label><input type="date" id="from" name="from" value="{{ $from }}" class="form-control"></div>
    <div class="col-auto"><label class="form-label" for="to">{{ __('To') }}</label><input type="date" id="to" name="to" value="{{ $to }}" class="form-control"></div>
    <div class="col-auto"><button class="btn btn-primary">{{ __('Show') }}</button></div>
</div></form>
