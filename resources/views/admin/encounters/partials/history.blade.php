<div class="tab-pane fade" id="history" role="tabpanel">
    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title">History</h3>
        </div>
        <div class="card-body">
            @if($encounter->histories->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Onset</th>
                            <th>Duration</th>
                            <th>Course</th>
                            <th>Severity</th>
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
                <p class="text-muted">No history recorded.</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.histories.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>History Type</label>
                                <select name="history_type" class="form-control" required>
                                    <option value="hpi">HPI</option>
                                    <option value="medical">Medical History</option>
                                    <option value="surgical">Surgical History</option>
                                    <option value="family">Family History</option>
                                    <option value="social">Social History</option>
                                    <option value="medication">Medication History</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Onset</label>
                                <input type="text" name="onset" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Duration</label>
                                <input type="text" name="duration" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Course</label>
                                <input type="text" name="course" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Severity</label>
                                <input type="text" name="severity" class="form-control">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Associated Symptoms</label>
                                <textarea name="associated_symptoms" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Clinical Notes</label>
                                <textarea name="clinical_notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Add History</button>
                </form>
            @endif
        </div>
    </div>
</div>
