@extends('layouts.adminlte')

@section('page_title', __('New journal'))

@section('page_content')
    @include('admin.finance._flash')
    <form method="POST" action="{{ route('admin.finance.journals.store') }}" class="card"><div class="card-body">@csrf
        <div class="row g-2 mb-3">
            <div class="col-md-2"><label class="form-label" for="date">{{ __('Date') }}</label><input type="date" id="date" name="date" class="form-control" required value="{{ old('date', now()->setTimezone('Asia/Dhaka')->toDateString()) }}"></div>
            <div class="col-md-6"><label class="form-label" for="memo">{{ __('What is this entry for?') }}</label><input id="memo" name="memo" class="form-control" required maxlength="250" value="{{ old('memo') }}"></div>
            <div class="col-md-4"><label class="form-label" for="reference">{{ __('Reference (document number)') }}</label><input id="reference" name="reference" class="form-control" maxlength="80" value="{{ old('reference') }}"></div>
        </div>
        <p class="text-muted small">{{ __('Each line is either a debit or a credit. The total debit must equal the total credit before it can be saved for approval.') }}</p>
        <div class="table-responsive"><table class="table table-sm" id="lines"><thead><tr><th>{{ __('Account') }}</th><th>{{ __('Cost centre') }}</th><th>{{ __('Whose (for receivables and payables)') }}</th><th class="text-end">{{ __('Debit') }}</th><th class="text-end">{{ __('Credit') }}</th><th>{{ __('Line memo') }}</th></tr></thead><tbody>
            @for($i = 0; $i < 6; $i++)<tr>
                <td><select name="lines[{{ $i }}][account]" class="form-select form-select-sm" aria-label="{{ __('Account') }}"><option value=""></option>@foreach($accounts as $a)<option value="{{ $a->id }}" @selected(old("lines.$i.account") == $a->id)>{{ $a->code }} {{ $a->name }}</option>@endforeach</select></td>
                <td><select name="lines[{{ $i }}][cost_center]" class="form-select form-select-sm" aria-label="{{ __('Cost centre') }}"><option value=""></option>@foreach($centers as $c)<option value="{{ $c->id }}" @selected(old("lines.$i.cost_center") == $c->id)>{{ $c->code }} {{ $c->name }}</option>@endforeach</select></td>
                <td class="d-flex gap-1"><select name="lines[{{ $i }}][party_type]" class="form-select form-select-sm" aria-label="{{ __('Kind of party') }}"><option value=""></option>@foreach(['patient', 'corporate', 'payer', 'supplier'] as $t)<option value="{{ $t }}">{{ __(\Illuminate\Support\Str::headline($t)) }}</option>@endforeach</select><input type="number" name="lines[{{ $i }}][party_id]" class="form-control form-control-sm" placeholder="{{ __('Number') }}" aria-label="{{ __('Party number') }}"></td>
                <td><input name="lines[{{ $i }}][debit]" inputmode="decimal" class="form-control form-control-sm text-end" value="{{ old("lines.$i.debit") }}" aria-label="{{ __('Debit') }}"></td>
                <td><input name="lines[{{ $i }}][credit]" inputmode="decimal" class="form-control form-control-sm text-end" value="{{ old("lines.$i.credit") }}" aria-label="{{ __('Credit') }}"></td>
                <td><input name="lines[{{ $i }}][memo]" class="form-control form-control-sm" maxlength="200" aria-label="{{ __('Line memo') }}"></td></tr>@endfor</tbody></table></div>
    </div><div class="card-footer d-flex gap-2"><button class="btn btn-outline-primary" name="submit" value="0">{{ __('Save as a draft') }}</button><button class="btn btn-primary" name="submit" value="1">{{ __('Save and send for approval') }}</button></div></form>
@stop
