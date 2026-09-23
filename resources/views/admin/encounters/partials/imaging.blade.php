@php($radiologyOrders = \App\Models\Radiology\RadiologyOrder::where('encounter_id', $encounter->id)->with(['items.procedure', 'items.examination.studies', 'items.examination.report'])->latest('ordered_at')->get())

<div class="tab-pane fade" id="imaging" role="tabpanel">
    <div class="card card-secondary card-outline">
        <div class="card-header">
            <h3 class="card-title">Imaging</h3>
        </div>
        <div class="card-body p-0">
            <table class="table table-sm mb-0">
                <thead>
                    <tr>
                        <th>Procedure</th>
                        <th>Accession #</th>
                        <th>Date</th>
                        <th>Radiologist</th>
                        <th>Impression</th>
                        <th>Report</th>
                        <th>Images</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($radiologyOrders as $radiologyOrder)
                        @forelse($radiologyOrder->items as $item)
                            @php($report = $item->examination?->report)
                            @php($study = $item->examination?->studies->first())
                            <tr>
                                <td>{{ $item->procedure?->name ?? $item->requested_procedure_name }}</td>
                                <td>{{ $radiologyOrder->accession_number }}</td>
                                <td>{{ $item->examination?->completed_at?->format('Y-m-d') ?? $radiologyOrder->ordered_at?->format('Y-m-d') }}</td>
                                <td>{{ $report?->radiologist?->name ?? '—' }}</td>
                                <td>{{ $report?->impression ? \Illuminate\Support\Str::limit($report->impression, 60) : '—' }}</td>
                                <td>
                                    @if($report)
                                        @can('radiology.report.view')
                                            <a href="{{ route('admin.radiology.reports.show', $report) }}" class="btn btn-xs btn-outline-primary">View Report</a>
                                        @endcan
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst(str_replace('_', ' ', $item->status)) }}</span>
                                    @endif
                                </td>
                                <td>
                                    @if($study?->hasImages())
                                        @can('radiology.study.view')
                                            <a href="{{ route('admin.radiology.studies.viewer', $study) }}" target="_blank" class="btn btn-xs btn-outline-secondary">
                                                <i class="bi bi-image"></i> View
                                            </a>
                                        @endcan
                                    @else
                                        <span class="text-muted">Not available</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                        @endforelse
                    @empty
                        <tr><td colspan="7" class="text-center text-muted py-3">No radiology orders for this encounter.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
