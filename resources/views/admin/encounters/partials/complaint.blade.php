<div class="tab-pane fade show active" id="complaint" role="tabpanel">
    <div class="card card-primary card-outline">
        <div class="card-header">
            <h3 class="card-title">Chief Complaint</h3>
        </div>
        <div class="card-body">
            @if($encounter->complaints->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Complaint</th>
                            <th>Duration</th>
                            <th>Onset</th>
                            <th>Severity</th>
                            <th>Location</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encounter->complaints as $complaint)
                            <tr>
                                <td>{{ $complaint->complaint }}</td>
                                <td>{{ $complaint->duration }} {{ $complaint->duration_unit }}</td>
                                <td>{{ $complaint->onset }}</td>
                                <td>{{ $complaint->severity }}</td>
                                <td>{{ $complaint->location }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No chief complaints recorded.</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.complaints.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Complaint</label>
                                <input type="text" name="complaint" class="form-control" required>
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
                                <label>Duration Unit</label>
                                <input type="text" name="duration_unit" class="form-control" placeholder="days">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Onset</label>
                                <input type="text" name="onset" class="form-control" placeholder="sudden">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Severity</label>
                                <input type="text" name="severity" class="form-control" placeholder="mild">
                            </div>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Location</label>
                                <input type="text" name="location" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-8">
                            <div class="form-group">
                                <label>Notes</label>
                                <textarea name="notes" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Add Complaint</button>
                </form>
            @endif
        </div>
    </div>
</div>
