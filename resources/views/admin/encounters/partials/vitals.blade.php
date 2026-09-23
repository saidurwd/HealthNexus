<div class="tab-pane fade" id="vitals" role="tabpanel">
    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title">Vital Signs</h3>
        </div>
        <div class="card-body">
            @if($encounter->vitals->isNotEmpty())
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Temp</th>
                                <th>BP</th>
                                <th>Pulse</th>
                                <th>RR</th>
                                <th>SpO2</th>
                                <th>Weight</th>
                                <th>Height</th>
                                <th>BMI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($encounter->vitals as $vital)
                                <tr>
                                    <td>{{ $vital->recorded_at->format('M d, Y H:i') }}</td>
                                    <td>{{ $vital->temperature }}°{{ $vital->temperature_unit ?? 'C' }}</td>
                                    <td>{{ $vital->systolic }}/{{ $vital->diastolic }} {{ $vital->bp_unit }}</td>
                                    <td>{{ $vital->pulse_rate }}</td>
                                    <td>{{ $vital->respiratory_rate }}</td>
                                    <td>{{ $vital->oxygen_saturation }}%</td>
                                    <td>{{ $vital->weight }} kg</td>
                                    <td>{{ $vital->height }} cm</td>
                                    <td>{{ $vital->bmi }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <p class="text-muted">No vitals recorded.</p>
            @endif
        </div>
    </div>
</div>
