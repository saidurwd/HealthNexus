@extends('layouts.adminlte')

@section('page_title', __('Immunisation card'))

@section('page_content')
    @include('admin.mch._flash')
    <div class="mb-3"><a class="btn btn-sm btn-outline-secondary" href="{{ route('admin.mch.immunisation') }}">{{ __('Back') }}</a></div>
    <div class="card"><div class="card-header"><h3 class="card-title">{{ $child->full_name }} <small class="text-muted">{{ $child->enterprise_patient_no }}</small></h3></div>
        @if(! $ready)<div class="card-body"><div class="alert alert-warning mb-0">{{ __('The child\'s date of birth is not recorded, so the schedule cannot be worked out.') }}</div></div>
        @else<div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Dose') }}</th><th>{{ __('Age') }}</th><th>{{ __('Due on') }}</th><th>{{ __('State') }}</th>@if($can['give'])<th>{{ __('Record') }}</th>@endif</tr></thead><tbody>
        @foreach($card as $row)<tr class="{{ $row['state'] === 'overdue' ? 'table-danger' : ($row['state'] === 'due' ? 'table-warning' : '') }}"><td>{{ $row['name'] }}</td><td>{{ __($row['group']) }}</td><td>{{ \Carbon\CarbonImmutable::parse($row['due_on'])->format('d M Y') }}</td>
            <td>{{ ['given' => __('Given').' '.($row['given_at'] ? \Carbon\CarbonImmutable::parse($row['given_at'], 'UTC')->setTimezone('Asia/Dhaka')->format('d M Y') : ''), 'due' => __('Due now'), 'overdue' => __('Overdue'), 'upcoming' => __('Not yet due')][$row['state']] }}</td>
            @if($can['give'])<td>@if($row['state'] !== 'given')<form method="POST" action="{{ route('admin.mch.immunisation.give', $child->id) }}" class="d-flex gap-1">@csrf<input type="hidden" name="vaccine" value="{{ $row['vaccine'] }}">
                <input name="batch_no" class="form-control form-control-sm" style="width:9rem" placeholder="{{ __('Batch number') }}" aria-label="{{ __('Batch number') }}"><button class="btn btn-sm btn-primary">{{ __('Give') }}</button>
                <button class="btn btn-sm btn-outline-secondary" name="status" value="recorded" title="{{ __('Given elsewhere, from the child\'s card') }}">{{ __('From card') }}</button></form>@endif</td>@endif</tr>@endforeach
        </tbody></table></div>@endif</div>
@stop
