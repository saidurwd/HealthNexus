@extends('layouts.adminlte')

@section('page_title', __('Welfare assessments'))

@section('page_content')
    @include('admin.welfare._flash')
    @php($H = fn ($v) => __(\Illuminate\Support\Str::headline((string) $v)))
    <p class="small text-muted">{{ __('Income and circumstances are private. The system works out income per person and suggests a category; the committee decides.') }}</p>
    <div class="d-flex gap-2 mb-2">@foreach(['submitted', 'draft', 'approved', 'rejected', 'expired', 'all'] as $s)<a class="btn btn-sm {{ $status === $s ? 'btn-primary' : 'btn-outline-secondary' }}" href="{{ route('admin.welfare.assessments', ['status' => $s]) }}">{{ $s === 'all' ? __('All') : $H($s) }}</a>@endforeach</div>
    <div class="card mb-3"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Number') }}</th><th>{{ __('Patient') }}</th><th class="text-end">{{ __('Income per person') }}</th><th>{{ __('Suggested') }}</th><th>{{ __('Status') }}</th><th>{{ __('Valid until') }}</th></tr></thead><tbody>
        @forelse($rows as $a)<tr><td><a href="{{ route('admin.welfare.assessments.show', $a->public_id) }}">{{ $a->number }}</a></td><td>{{ $a->patient?->display_name }} <span class="text-muted">{{ $a->patient?->enterprise_patient_no }}</span></td><td class="text-end">{{ number_format((float) $a->per_head, 2) }}</td><td>{{ $H($a->category) }}@if($a->suggested_percent) · {{ $a->suggested_percent }}%@endif</td><td><x-ui.status-badge :status="$a->status" :label="$H($a->status)" /></td><td>{{ $a->valid_until?->format('d M Y') }}</td></tr>@empty<tr><td colspan="6" class="text-muted">{{ __('Nothing here.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    @if($canCreate)<form method="POST" action="{{ route('admin.welfare.assessments.store') }}" class="card">@csrf<div class="card-header"><h3 class="card-title">{{ __('Assess a patient') }}</h3></div><div class="card-body row g-2">
        <div class="col-md-3"><label class="form-label">{{ __('Patient number') }}</label><input name="patient_no" class="form-control form-control-sm" required value="{{ old('patient_no') }}"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Household income per month (৳)') }}</label><input type="number" step="0.01" min="0" name="monthly_income" class="form-control form-control-sm" required value="{{ old('monthly_income') }}"></div>
        <div class="col-md-3"><label class="form-label">{{ __('People the income supports') }}</label><input type="number" min="1" max="40" name="household_size" class="form-control form-control-sm" required value="{{ old('household_size') }}"></div>
        <div class="col-md-3"><label class="form-label">{{ __('Work of the main earner') }}</label><input name="occupation" class="form-control form-control-sm" maxlength="80" value="{{ old('occupation') }}"></div>
        <div class="col-12"><label class="form-label d-block">{{ __('Circumstances') }}</label>@foreach($factors as $f)<div class="form-check form-check-inline"><input class="form-check-input" type="checkbox" name="factors[]" value="{{ $f }}" id="f-{{ $f }}"><label class="form-check-label" for="f-{{ $f }}">{{ $H($f) }}</label></div>@endforeach</div>
        <div class="col-12"><label class="form-label">{{ __('What was seen or checked') }}</label><input name="evidence_note" class="form-control form-control-sm" maxlength="500" value="{{ old('evidence_note') }}"></div>
        <div class="col-12"><button class="btn btn-primary btn-sm">{{ __('Start the assessment') }}</button></div></div></form>@endif
@stop
