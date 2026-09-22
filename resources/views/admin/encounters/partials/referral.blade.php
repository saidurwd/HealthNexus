<div class="tab-pane fade" id="referral" role="tabpanel">
    <div class="card card-warning card-outline">
        <div class="card-header">
            <h3 class="card-title">Referrals</h3>
        </div>
        <div class="card-body">
            @if($encounter->referrals->isNotEmpty())
                <table class="table table-sm">
                    <thead>
                        <tr>
                            <th>Type</th>
                            <th>Referred To</th>
                            <th>Reason</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($encounter->referrals as $referral)
                            <tr>
                                <td>{{ $referral->referral_type }}</td>
                                <td>{{ $referral->referred_to }}</td>
                                <td>{{ $referral->reason }}</td>
                                <td>{{ $referral->status }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            @else
                <p class="text-muted">No referrals recorded.</p>
            @endif

            @if(!$encounter->locked_at)
                <form method="POST" action="{{ route('admin.encounters.referrals.store', $encounter) }}" class="mt-3">
                    @csrf
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Referral Type</label>
                                <select name="referral_type" class="form-control" required>
                                    <option value="internal">Internal</option>
                                    <option value="external">External</option>
                                    <option value="department">Department</option>
                                    <option value="provider">Provider</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Referred To</label>
                                <input type="text" name="referred_to" class="form-control">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Reason</label>
                                <textarea name="reason" class="form-control" rows="1"></textarea>
                            </div>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary mt-2">Create Referral</button>
                </form>
            @endif
        </div>
    </div>
</div>
