@extends('layouts.adminlte')

@section('page_title', __('Finance settings'))

@section('page_content')
    @include('admin.finance._flash')
    <p class="text-muted small">{{ __('Starting values. The hospital\'s accountant should confirm them. Nothing here sets a tax rate.') }}</p>
    <form method="POST" action="{{ route('admin.finance.settings.save') }}" class="card"><div class="card-body row g-3">@csrf
        @foreach(['maker_checker' => 'A manual journal needs a second person to approve it', 'self_approval' => 'The person who wrote a journal may approve it (turn off in any hospital with more than one accountant)', 'locked_posting' => 'Automatic postings may enter a locked period', 'writeoff_second' => 'A write-off needs a second person', 'payment_second' => 'A supplier payment needs a second person'] as $k => $label)
            <div class="col-12"><input type="hidden" name="{{ $k }}" value="0"><div class="form-check"><input type="checkbox" class="form-check-input" id="{{ $k }}" name="{{ $k }}" value="1" @checked($v[$k])><label class="form-check-label" for="{{ $k }}">{{ __($label) }}</label></div></div>
        @endforeach
        <div class="col-md-3"><label class="form-label" for="first_month">{{ __('First month of the fiscal year') }}</label><input id="first_month" name="first_month" type="number" min="1" max="12" class="form-control" value="{{ $v['first_month'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="budget_mode">{{ __('Budget check on purchases') }}</label><select id="budget_mode" name="budget_mode" class="form-select">@foreach(['off' => 'Off', 'warn' => 'Warn', 'block' => 'Block'] as $o => $l)<option value="{{ $o }}" @selected($v['budget_mode'] === $o)>{{ __($l) }}</option>@endforeach</select></div>
        <div class="col-md-3"><label class="form-label" for="catch_up">{{ __('Look back for missed postings (days)') }}</label><input id="catch_up" name="catch_up" type="number" min="1" max="365" class="form-control" value="{{ $v['catch_up'] }}"></div>
        <div class="col-md-3"><label class="form-label" for="asset_life">{{ __('Default life of an asset (months)') }}</label><input id="asset_life" name="asset_life" type="number" min="1" max="600" class="form-control" value="{{ $v['asset_life'] }}"></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save') }}</button></div></form>
@stop
