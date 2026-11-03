@extends('layouts.adminlte')

@section('page_title', __('Notify a birth'))

@section('page_content')
    @include('admin.vital._flash')
    <form method="POST" action="{{ route('admin.vital.births.store') }}" class="card">@csrf
        <div class="card-body row g-3">
            <div class="col-md-6"><label class="form-label" for="b-mother">{{ __('Mother') }}</label><x-ui.patient-select name="mother_patient_id" id="b-mother" required /></div>
            <div class="col-md-3"><label class="form-label" for="b-born">{{ __('Date and time of birth') }}</label><input id="b-born" type="datetime-local" name="born_at" value="{{ old('born_at') }}" class="form-control" required></div>
            <div class="col-md-3"><label class="form-label" for="b-outcome">{{ __('Outcome') }}</label><select id="b-outcome" name="outcome" class="form-select">@foreach(config('vital.outcomes') as $o)<option value="{{ $o }}" @selected(old('outcome') === $o)>{{ $o === 'stillbirth' ? __('Stillbirth') : __('Live birth') }}</option>@endforeach</select></div>
            <div class="col-md-4"><label class="form-label" for="b-name">{{ __('Name of the child (if named)') }}</label><input id="b-name" name="child_name" value="{{ old('child_name') }}" class="form-control" maxlength="150"></div>
            <div class="col-md-2"><label class="form-label" for="b-sex">{{ __('Sex') }}</label><select id="b-sex" name="sex" class="form-select" required>@foreach(config('vital.sexes') as $x)<option value="{{ $x }}" @selected(old('sex') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="b-place">{{ __('Place of birth') }}</label><select id="b-place" name="place_of_birth" class="form-select">@foreach(config('vital.places') as $x)<option value="{{ $x }}" @selected(old('place_of_birth') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <div class="col-md-3"><label class="form-label" for="b-delivery">{{ __('Delivery') }}</label><select id="b-delivery" name="delivery_type" class="form-select"><option value=""></option>@foreach(config('vital.delivery_types') as $x)<option value="{{ $x }}" @selected(old('delivery_type') === $x)>{{ __(\Illuminate\Support\Str::headline($x)) }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label" for="b-weight">{{ __('Weight (grams)') }}</label><input id="b-weight" type="number" name="weight_g" value="{{ old('weight_g') }}" class="form-control" min="200" max="7000"></div>
            <div class="col-md-2"><label class="form-label" for="b-weeks">{{ __('Weeks of pregnancy') }}</label><input id="b-weeks" type="number" name="gestation_weeks" value="{{ old('gestation_weeks') }}" class="form-control" min="12" max="46"></div>
            <div class="col-md-2"><label class="form-label" for="b-plural">{{ __('Babies in this birth') }}</label><input id="b-plural" type="number" name="plurality" value="{{ old('plurality', 1) }}" class="form-control" min="1" max="6"></div>
            <div class="col-md-2"><label class="form-label" for="b-order">{{ __('This baby is number') }}</label><input id="b-order" type="number" name="birth_order" value="{{ old('birth_order', 1) }}" class="form-control" min="1" max="6"></div>
            <div class="col-md-6"><label class="form-label" for="b-attendant">{{ __('Who attended the birth') }}</label><input id="b-attendant" name="attendant_name" value="{{ old('attendant_name') }}" class="form-control" maxlength="150"></div>
            <div class="col-md-6"><label class="form-label" for="b-father">{{ __('Father\'s name') }}</label><input id="b-father" name="father_name" value="{{ old('father_name') }}" class="form-control" maxlength="150"></div>
            <div class="col-md-4"><label class="form-label" for="b-fnid">{{ __('Father\'s national ID number') }}</label><input id="b-fnid" name="father_nid" value="{{ old('father_nid') }}" class="form-control" maxlength="30" autocomplete="off"></div>
            <div class="col-md-4"><label class="form-label" for="b-informant">{{ __('Informant') }}</label><input id="b-informant" name="informant_name" value="{{ old('informant_name') }}" class="form-control" maxlength="150"></div>
            <div class="col-md-4"><label class="form-label" for="b-irel">{{ __('Informant\'s relation to the mother') }}</label><input id="b-irel" name="informant_relation" value="{{ old('informant_relation') }}" class="form-control" maxlength="60"></div>
            <div class="col-12"><hr class="my-1"><div class="small text-muted mb-2">{{ __('Where the family lives') }}</div></div>
            <div class="col-md-3"><label class="form-label" for="b-division">{{ __('Division') }}</label><x-ui.geo-select level="division" name="division_id" id="b-division" /></div>
            <div class="col-md-3"><label class="form-label" for="b-district">{{ __('District') }}</label><x-ui.geo-select level="district" name="district_id" id="b-district" depends-on="#b-division" /></div>
            <div class="col-md-3"><label class="form-label" for="b-upazila">{{ __('Upazila') }}</label><x-ui.geo-select level="upazila" name="upazila_id" id="b-upazila" depends-on="#b-district" /></div>
            <div class="col-md-3"><label class="form-label" for="b-union">{{ __('Union') }}</label><x-ui.geo-select level="union" name="union_id" id="b-union" depends-on="#b-upazila" /></div>
            <div class="col-md-6"><label class="form-label" for="b-village">{{ __('Village or neighbourhood') }}</label><input id="b-village" name="village_area" value="{{ old('village_area') }}" class="form-control" maxlength="120"></div>
        </div>
        <div class="card-footer"><button class="btn btn-primary">{{ __('Save the notification') }}</button> <a class="btn btn-link" href="{{ route('admin.vital.births') }}">{{ __('Cancel') }}</a></div>
    </form>
@stop
