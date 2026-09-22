<div class="tab-pane fade" id="problems" role="tabpanel">
    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title">Problem List</h3>
        </div>
        <div class="card-body">
            @if($encounter->problems->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Problem</th>
                            <th>Code</th>
                            <th>Status</th>
                            <th>Onset</th>
                            <th>Resolved</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encounter->problems as $problem)
                            <tr>
                                <td>{{ $problem->problem_name }}</td>
                                <td>{{ $problem->problem_code }} ({{ $problem->coding_system }})</td>
                                <td>{{ $problem->status }}</td>
                                <td>{{ $problem->onset_date }}</td>
                                <td>{{ $problem->resolved_date ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No problems recorded.</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.problems.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Problem Name</label>
                                <input type="text" name="problem_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Code</label>
                                <input type="text" name="problem_code" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Coding System</label>
                                <input type="text" name="coding_system" class="form-control" placeholder="ICD-10">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Status</label>
                                <select name="status" class="form-control">
                                    <option value="active">Active</option>
                                    <option value="resolved">Resolved</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>Onset Date</label>
                                <input type="date" name="onset_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Add Problem</button>
                </form>
            @endif
        </div>
    </div>
</div>
