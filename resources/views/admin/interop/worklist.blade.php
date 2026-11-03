@extends('layouts.adminlte')

@section('page_title', __('Scanner worklist'))

@section('page_content')
    @include('admin.interop._flash')
    <p class="text-muted small">{{ __('The list a scanner asks for to learn which patient is next. It is made from radiology orders; the accession number ties the images back to the order.') }}</p>
    <div class="d-flex justify-content-between mb-3"><form method="GET"><select name="status" class="form-select w-auto" aria-label="{{ __('Status') }}" onchange="this.form.submit()">@foreach(['scheduled' => 'Waiting', 'in_progress' => 'In progress', 'completed' => 'Done', 'cancelled' => 'Cancelled', 'all' => 'All'] as $k => $t)<option value="{{ $k }}" @selected($status === $k)>{{ __($t) }}</option>@endforeach</select></form>
        @can('interoperability.dicom.worklist.manage')<form method="POST" action="{{ route('admin.interop.worklist.sync') }}">@csrf<button class="btn btn-outline-secondary">{{ __('Update from radiology now') }}</button></form>@endcan</div>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Accession') }}</th><th>{{ __('Patient (as the scanner sees it)') }}</th><th>{{ __('Number') }}</th><th>{{ __('Modality') }}</th><th>{{ __('Scanner') }}</th><th>{{ __('Exam') }}</th><th>{{ __('Planned') }}</th><th>{{ __('Status') }}</th></tr></thead>
        <tbody>@forelse($rows as $e)<tr><td><code>{{ $e->accession_number }}</code></td><td>{{ $e->patient_name }}</td><td>{{ $e->patient_mrn }}</td><td>{{ $e->modality }}</td><td>{{ $e->station_ae_title }}</td><td>{{ $e->procedure_description }}</td><td>{{ $e->scheduled_at?->format('d M H:i') }}</td><td><x-ui.status-badge :status="$e->status" /></td></tr>
        @empty<tr><td colspan="8" class="text-muted">{{ __('Nothing on the list.') }}</td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
@stop
