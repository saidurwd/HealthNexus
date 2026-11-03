<div class="tab-pane fade" id="diagnosis" role="tabpanel">
    <div class="card card-danger card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ __('Diagnoses') }}</h3>
        </div>
        <div class="card-body">
            @if($encounter->diagnoses->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Description') }}</th>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Primary') }}</th>
                            <th>{{ __('Status') }}</th>
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
                <p class="text-muted">{{ __('No diagnoses recorded.') }}</p>
            @endif
        </div>
    </div>
</div>
