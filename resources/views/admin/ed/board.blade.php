@extends('layouts.adminlte')

@section('page_title', __('Emergency tracking board'))

@section('page_content')
    <div class="d-flex flex-wrap gap-2 align-items-center mb-3">
        @can('ed.visit.create')
            <a href="{{ route('admin.ed.arrivals.create') }}" class="btn btn-danger btn-lg"><i class="bi bi-person-plus" aria-hidden="true"></i> {{ __('New arrival') }}</a>
        @endcan
        <span class="text-muted ms-auto small">{{ __('Updates every :n seconds', ['n' => $pollSeconds]) }} · <span id="ed-updated">—</span></span>
    </div>

    @if($mci)
        <div class="alert alert-danger d-flex flex-wrap gap-3 align-items-center" role="alert">
            <strong><i class="bi bi-exclamation-triangle-fill" aria-hidden="true"></i> {{ __('Mass-casualty mode is on') }}</strong>
            @foreach($mciCounts as $level => $total)<span class="badge text-bg-dark">{{ $level === 'untriaged' ? __('Not triaged') : __('Level').' '.$level }}: {{ $total }}</span>@endforeach
            @can('ed.mci.manage')
                <form method="POST" action="{{ route('admin.ed.mci') }}" class="ms-auto">@csrf<input type="hidden" name="action" value="deactivate"><button class="btn btn-sm btn-light">{{ __('Turn off') }}</button></form>
            @endcan
        </div>
    @else
        @can('ed.mci.manage')
            <form method="POST" action="{{ route('admin.ed.mci') }}" class="d-flex gap-2 mb-3">@csrf<input type="hidden" name="action" value="activate">
                <input type="text" name="reason" class="form-control w-auto" maxlength="300" placeholder="{{ __('Reason (optional)') }}">
                <button class="btn btn-outline-danger">{{ __('Turn on mass-casualty mode') }}</button></form>
        @endcan
    @endif

    <div class="row mb-3" id="ed-counts"></div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-sm table-hover mb-0" id="ed-board">
                <thead><tr>
                    <th>{{ __('Level') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Complaint') }}</th><th>{{ __('Status') }}</th>
                    <th>{{ __('Bay') }}</th><th>{{ __('Doctor') }}</th><th>{{ __('Nurse') }}</th><th>{{ __('Waiting') }}</th>
                </tr></thead>
                <tbody id="ed-rows"></tbody>
            </table>
        </div>
        <div id="ed-empty" class="d-none"><x-ui.empty-state :title="__('No patients in the emergency department.')" :hint="__('New arrivals appear here at once.')" icon="bi-hospital" /></div>
    </div>

    <div class="card mt-3">
        <div class="card-header"><h3 class="card-title">{{ __('Bays') }}</h3></div>
        <div class="card-body" id="ed-bays"></div>
    </div>
@stop

@push('js')
<script>
(function () {
    var url = @json(route('admin.ed.board.data'));
    var visitBase = @json(url('/admin/emergency/visits'));
    var poll = {{ $pollSeconds }} * 1000;
    var L = @json($labels);
    var statusText = @json($statusText);
    var colours = {danger: '#dc3545', orange: '#fd7e14', warning: '#ffc107', success: '#198754', info: '#0dcaf0'};

    function esc(s) { return String(s == null ? '' : s).replace(/[&<>"']/g, function (c) { return {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;'}[c]; }); }

    function render(data) {
        var b = data.board, rows = '';
        b.visits.forEach(function (v) {
            var lvl = v.level ? b.levels[v.level] : null;
            var colour = lvl ? (colours[lvl.color] || '#6c757d') : '#6c757d';
            var flags = '';
            if (v.door_to_doctor_breach) flags += ' <span class="badge text-bg-danger"><i class="bi bi-alarm"></i> ' + esc(L.late) + '</span>';
            if (v.retriage_due) flags += ' <span class="badge text-bg-warning"><i class="bi bi-arrow-repeat"></i> ' + esc(L.retriage) + '</span>';
            if (v.triage_overdue) flags += ' <span class="badge text-bg-warning"><i class="bi bi-hourglass-split"></i> ' + esc(L.triage) + '</span>';
            if (v.mass_casualty) flags += ' <span class="badge text-bg-dark">' + esc(L.mci) + '</span>';
            rows += '<tr data-id="' + esc(v.id) + '" style="cursor:pointer">'
                + '<td><span class="badge" style="background:' + colour + ';color:#000;font-size:1rem">' + (v.level || '?') + '</span> <small>' + esc(lvl ? lvl.name : L.none) + '</small></td>'
                + '<td><strong>' + esc(v.patient) + '</strong>' + (v.unknown ? ' <span class="badge text-bg-secondary">' + esc(L.unknown) + '</span>' : '') + '<br><small class="text-muted">' + esc(v.visit_number) + ' · ' + esc(v.mrn) + '</small></td>'
                + '<td>' + esc(v.complaint) + flags + '</td>'
                + '<td>' + esc(statusText[v.status] || v.status) + '</td>'
                + '<td>' + esc(v.bay || '—') + '<br><small class="text-muted">' + esc(v.zone || '') + '</small></td>'
                + '<td>' + esc(v.doctor || '—') + '</td><td>' + esc(v.nurse || '—') + '</td>'
                + '<td>' + v.wait_minutes + ' ' + esc(L.waiting) + '<br><small class="text-muted">' + esc(v.arrived_at) + '</small></td></tr>';
        });
        document.getElementById('ed-rows').innerHTML = rows;
        document.getElementById('ed-empty').classList.toggle('d-none', b.visits.length > 0);
        document.getElementById('ed-board').classList.toggle('d-none', b.visits.length === 0);

        var counts = '';
        Object.keys(b.counts).forEach(function (k) {
            counts += '<div class="col"><div class="card"><div class="card-body text-center py-2"><div class="h4 mb-0">' + b.counts[k] + '</div><small class="text-muted">' + esc(k === 'total' ? @json($labels['total']) : (statusText[k] || k)) + '</small></div></div></div>';
        });
        document.getElementById('ed-counts').innerHTML = counts;

        var bays = '';
        data.bays.forEach(function (z) {
            bays += '<div class="mb-2"><strong>' + esc(z.zone) + '</strong> ';
            z.bays.forEach(function (bay) {
                var cls = bay.status === 'available' ? 'text-bg-success' : (bay.status === 'occupied' ? 'text-bg-danger' : 'text-bg-secondary');
                bays += '<span class="badge ' + cls + ' me-1">' + esc(bay.code) + (bay.visit ? ' · ' + esc(bay.visit) : '') + '</span>';
            });
            bays += '</div>';
        });
        document.getElementById('ed-bays').innerHTML = bays || '<span class="text-muted">—</span>';
        document.getElementById('ed-updated').textContent = new Date().toLocaleTimeString();
    }

    document.getElementById('ed-rows').addEventListener('click', function (e) {
        var tr = e.target.closest('tr[data-id]');
        if (tr) { window.location = visitBase + '/' + tr.dataset.id; }
    });

    function refresh() {
        fetch(url, {headers: {'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest'}, credentials: 'same-origin'})
            .then(function (r) { return r.ok ? r.json() : Promise.reject(r.status); })
            .then(render)
            .catch(function () { document.getElementById('ed-updated').textContent = @json($labels['lost']); });
    }

    render({board: @json($board), bays: @json($bays)});
    setInterval(refresh, poll);
})();
</script>
@endpush
