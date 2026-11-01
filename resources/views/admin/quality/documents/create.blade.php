@extends('layouts.adminlte')

@section('page_title', __('New document'))

@section('page_content')
    @include('admin.quality._flash')
    <form method="POST" action="{{ route('admin.quality.documents.store') }}" class="card"><div class="card-body row g-3">@csrf
        <div class="col-md-3"><label class="form-label">{{ __('Code') }}</label><input name="code" class="form-control" maxlength="30" value="{{ old('code') }}" placeholder="IPC-01" required></div>
        <div class="col-md-5"><label class="form-label">{{ __('Title') }}</label><input name="title" class="form-control" maxlength="200" value="{{ old('title') }}" required></div>
        <div class="col-md-2"><label class="form-label">{{ __('Kind') }}</label><select name="category" class="form-select">@foreach($categories as $c)<option value="{{ $c }}">{{ __(\Illuminate\Support\Str::headline($c)) }}</option>@endforeach</select></div>
        <div class="col-md-2"><label class="form-label">{{ __('Review every (months)') }}</label><input type="number" name="review_months" class="form-control" min="1" max="60" value="{{ old('review_months', $months) }}"></div>
        <div class="col-md-4"><label class="form-label">{{ __('Department') }}</label><select name="department_id" class="form-select"><option value="">—</option>@foreach($departments as $d)<option value="{{ $d->id }}">{{ $d->name }}</option>@endforeach</select></div>
        <div class="col-12"><label class="form-label">{{ __('Text') }}</label><textarea name="body" class="form-control" rows="14" required>{{ old('body') }}</textarea></div>
    </div><div class="card-footer"><button class="btn btn-primary">{{ __('Save as a draft') }}</button></div></form>
@stop
