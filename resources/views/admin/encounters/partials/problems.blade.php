<div class="tab-pane fade" id="problems" role="tabpanel">
    <div class="card card-info card-outline">
        <div class="card-header">
            <h3 class="card-title">{{ __('Problem List') }}</h3>
        </div>
        <div class="card-body">
            @if($encounter->problems->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>{{ __('Problem') }}</th>
                            <th>{{ __('Code') }}</th>
                            <th>{{ __('Status') }}</th>
                            <th>{{ __('Onset') }}</th>
                            <th>{{ __('Resolved') }}</th>
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
                <p class="text-muted">{{ __('No problems recorded.') }}</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.problems.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>{{ __('Problem Name') }}</label>
                                <input type="text" name="problem_name" class="form-control" required>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Code') }}</label>
                                <input type="text" name="problem_code" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Coding System') }}</label>
                                <input type="text" name="coding_system" class="form-control" placeholder="{{ __('ICD-10') }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Status') }}</label>
                                <select name="status" class="form-control">
                                    <option value="active">{{ __('Active') }}</option>
                                    <option value="resolved">{{ __('Resolved') }}</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-2">
                            <div class="form-group">
                                <label>{{ __('Onset Date') }}</label>
                                <input type="date" name="onset_date" class="form-control">
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">{{ __('Add Problem') }}</button>
                </form>
            @endif
        </div>
    </div>
</div>
