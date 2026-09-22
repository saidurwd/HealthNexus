<div class="tab-pane fade" id="prescription" role="tabpanel">
    <div class="card card-success card-outline">
        <div class="card-header">
            <h3 class="card-title">Prescriptions</h3>
        </div>
        <div class="card-body">
            @if($encounter->prescriptions->isNotEmpty())
                @foreach($encounter->prescriptions as $prescription)
                    <div class="callout callout-success">
                        <h5>Prescription #{{ $prescription->prescription_no }}</h5>
                        <p class="mb-1"><strong>Notes:</strong> {{ $prescription->clinical_notes ?? '-' }}</p>
                        <p class="mb-0"><strong>Advice:</strong> {{ $prescription->advice ?? '-' }}</p>
                        @if($prescription->items->isNotEmpty())
                            <table class="table table-sm mt-2">
                                <thead>
                                    <tr>
                                        <th>Medicine</th>
                                        <th>Dose</th>
                                        <th>Frequency</th>
                                        <th>Duration</th>
                                        <th>Qty</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($prescription->items as $item)
                                        <tr>
                                            <td>{{ $item->medicine_name }}</td>
                                            <td>{{ $item->dosage }} {{ $item->strength }}</td>
                                            <td>{{ $item->frequency }}</td>
                                            <td>{{ $item->duration }} {{ $item->duration_unit }}</td>
                                            <td>{{ $item->quantity }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @endif
                    </div>
                @endforeach
            @else
                <p class="text-muted">No prescriptions recorded.</p>
            @endif
        </div>
    </div>
</div>
