@php($labOrders = \App\Models\Laboratory\LabOrder::where('encounter_id', $encounter->id)->with(['items.test', 'reports'])->latest('ordered_at')->get())

<div class="tab-pane fade" id="lab-results" role="tabpanel">
    <div class="card card-secondary card-outline">
        <div class="card-header">
            <h3 class="card-title">Lab Results</h3>
        </div>
        <div class="card-body">
            @forelse($labOrders as $labOrder)
                <div class="mb-3 pb-3 border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <strong>{{ $labOrder->order_number }}</strong>
                        <span class="badge bg-info">{{ ucfirst(str_replace('_', ' ', $labOrder->status)) }}</span>
                    </div>
                    <table class="table table-sm mb-2">
                        <thead><tr><th>Test</th><th>Result</th><th>Flag</th></tr></thead>
                        <tbody>
                            @foreach($labOrder->items as $item)
                                @php($result = $item->results->firstWhere('is_current', true))
                                <tr>
                                    <td>{{ $item->test?->name ?? $item->requested_test_name }}</td>
                                    <td>
                                        @if($result)
                                            {{ $result->numeric_value ?? $result->qualitative_value ?? $result->text_value }} {{ $result->unit }}
                                        @else
                                            <span class="text-muted">Pending</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($result && $result->abnormal_flag && $result->abnormal_flag !== 'normal')
                                            <span class="badge bg-danger">{{ strtoupper($result->abnormal_flag) }}</span>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    @if($labOrder->reports->isNotEmpty())
                        @can('lab.report.view')
                            <a href="{{ route('admin.lab.reports.show', $labOrder->reports->first()) }}" class="btn btn-xs btn-outline-primary">View Report</a>
                        @endcan
                    @endif
                </div>
            @empty
                <p class="text-muted">No lab orders for this encounter.</p>
            @endforelse
        </div>
    </div>
</div>
