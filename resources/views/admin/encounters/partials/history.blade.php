<div class="tab-pane fade" id="history" role="tabpanel">
    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ __('History') }}</h3>
        </div>
        <div class="card-body">
            @if($encounter->histories->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Type') }}</th>
                            <th>{{ __('Onset') }}</th>
                            <th>{{ __('Duration') }}</th>
                            <th>{{ __('Course') }}</th>
                            <th>{{ __('Severity') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encounter->histories as $history)
                            <tr>
                                <td>{{ $history->history_type }}</td>
                                <td>{{ $history->onset }}</td>
                                <td>{{ $history->duration }}</td>
                                <td>{{ $history->course }}</td>
                                <td>{{ $history->severity }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">{{ __('No history recorded.') }}</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.histories.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('History Type') }}</label>
                                <select name="history_type" class="form-control" required>
                                    <option value="hpi">{{ __('HPI') }}</option>
                                    <option value="medical">{{ __('Medical History') }}</option>
                                    <option value="surgical">{{ __('Surgical History') }}</option>
                                    <option value="family">{{ __('Family History') }}</option>
                                    <option value="social">{{ __('Social History') }}</option>
                                    <option value="medication">{{ __('Medication History') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Onset') }}</label>
                                <input type="text" name="onset" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Duration') }}</label>
                                <input type="text" name="duration" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Course') }}</label>
                                <input type="text" name="course" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>{{ __('Severity') }}</label>
                                <input type="text" name="severity" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Associated Symptoms') }}</label>
                                <textarea name="associated_symptoms" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>{{ __('Clinical Notes') }}</label>
                                <textarea name="clinical_notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">{{ __('Add History') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
