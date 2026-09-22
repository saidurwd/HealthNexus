<div class="tab-pane fade" id="diagnosis" role="tabpanel">
    <div class="card card-danger card-outline">
        <div class="card-header">
            <h3 class="card-title">Diagnoses</h3>
        </div>
        <div class="card-body">
            @if($encounter->diagnoses->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Description</th>
                            <th>Type</th>
                            <th>Primary</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encounter->diagnoses as $diagnosis)
                            <tr>
                                <td>{{ $diagnosis->code }} ({{ $diagnosis->code_type }})</td>
                                <td>{{ $diagnosis->description }}</td>
                                <td>{{ $diagnosis->diagnosis_type ?? '-' }}</td>
                                <td>{{ $diagnosis->is_primary ? 'Yes' : 'No' }}</td>
                                <td>{{ $diagnosis->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No diagnoses recorded.</p>
            @endif
        </div>
    </div>
</div>
