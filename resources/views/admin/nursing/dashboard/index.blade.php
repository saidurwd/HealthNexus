@extends('layouts.adminlte')

@section('page_title', __('Nursing'))

@section('page_content')
    @php $L = fn ($route, $perm, $q = []) => \App\Support\Dashboard\Link::to($route, $perm, $q); @endphp
    <x-ui.dashboard :question="__('What is overdue on the ward, and what is mine?')" :audience="__('For the nurse in charge and the ward nurses')" :as-of="$asOf" :refresh="config('ux.dashboard.refresh_seconds.board')">
        <div class="row row-cols-2 row-cols-md-4 row-cols-xl-5 g-2 mb-3">
            <x-ui.kpi-tile :label="__('My patients')" :value="$metrics['my_patients']" unit="count" icon="bi-person-heart" variant="primary" :href="$L('admin.nursing.episodes.index', 'nursing.dashboard.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('Patients on the ward')" :value="$metrics['ward_patients']" unit="count" icon="bi-hospital" variant="info" :href="$L('admin.nursing.episodes.index', 'nursing.dashboard.view')" good-direction="none" />
            <x-ui.kpi-tile :label="__('My tasks overdue')" :value="$metrics['my_overdue_tasks']" unit="count" icon="bi-alarm" variant="danger" :href="$L('admin.nursing.tasks.index', 'nursing.task.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Tasks overdue on the ward')" :value="$metrics['overdue_tasks']" unit="count" icon="bi-alarm" variant="danger" :href="$L('admin.nursing.tasks.index', 'nursing.task.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Medicines overdue')" :value="$metrics['overdue_medications']" unit="count" icon="bi-capsule" variant="danger" :href="$L('admin.nursing.mar.index', 'nursing.mar.view')" good-direction="down" :note="__(':n due in all', ['n' => \App\Support\Dashboard\Numbers::number($metrics['due_medications'])])" />
            <x-ui.kpi-tile :label="__('Tasks to do')" :value="$metrics['pending_tasks']" unit="count" icon="bi-list-check" variant="warning" :href="$L('admin.nursing.tasks.index', 'nursing.task.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Serious alerts open')" :value="$metrics['critical_alerts']" unit="count" icon="bi-exclamation-octagon" variant="danger" :href="$L('admin.nursing.escalations.index', 'nursing.escalation.view')" good-direction="down" />
            <x-ui.kpi-tile :label="__('Assessments in draft')" :value="$metrics['pending_assessments']" unit="count" icon="bi-clipboard2" variant="secondary" good-direction="down" />
            <x-ui.kpi-tile :label="__('Handovers to acknowledge')" :value="$metrics['pending_handover']" unit="count" icon="bi-arrow-repeat" variant="warning" :href="$L('admin.nursing.handover.index', 'nursing.handover.view')" good-direction="down" />
        </div>
        <div class="card mb-3"><div class="card-header"><h3 class="card-title">{{ __('Tasks planned and done, last 14 days') }}</h3></div><div class="card-body">
            <x-ui.chart kind="bar" :rows="$metrics['tasks']" x="day" :series="[['key' => 'planned', 'label' => 'Planned'], ['key' => 'done', 'label' => 'Done']]" />
        </div></div>
        <div class="card"><div class="card-header"><h3 class="card-title">{{ __('My patients') }}</h3></div>
            <div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Ward / bed') }}</th><th>{{ __('Since') }}</th></tr></thead><tbody>
                @forelse($myPatients as $e)<tr><td>{{ $e->patient?->display_name }} <span class="text-muted">{{ $e->patient?->enterprise_patient_no }}</span></td><td>{{ $e->admission?->currentAllocation?->bed?->room?->ward?->name }} {{ $e->admission?->currentAllocation?->bed?->bed_code }}</td><td>{{ $e->start_at?->timezone('Asia/Dhaka')->format('d M, H:i') }}</td></tr>@empty<tr><td colspan="3"><x-ui.empty-state :title="__('No patients are assigned to you.')" :hint="__('Patients appear here when the nurse in charge assigns them.')" icon="bi-person-heart" /></td></tr>@endforelse</tbody></table></div></div>
    </x-ui.dashboard>
@stop
