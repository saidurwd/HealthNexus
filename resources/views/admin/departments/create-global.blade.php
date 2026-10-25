@extends('layouts.adminlte')

@section('page_title', 'Create Department')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Select Organization and Branch</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.organizations.branches.departments.create') }}" id="departmentCreateForm">
                <div class="mb-3">
                    <label for="organization_id" class="form-label">Organization</label>
                    <select class="form-control" id="organization_id" name="organization" required>
                        <option value="">Select Organization</option>
                        @foreach ($organizations as $organization)
                            <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select class="form-control" id="branch_id" name="branch" required disabled>
                        <option value="">Select Organization First</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="continueBtn" disabled>Continue</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('organization_id').addEventListener('change', function() {
    const organizationId = this.value;
    const branchSelect = document.getElementById('branch_id');
    const continueBtn = document.getElementById('continueBtn');

    if (!organizationId) {
        branchSelect.innerHTML = '<option value="">Select Organization First</option>';
        branchSelect.disabled = true;
        continueBtn.disabled = true;
        return;
    }

    fetch(`/api/v1/organizations/${organizationId}/branches`)
        .then(response => response.json())
        .then(data => {
            branchSelect.innerHTML = '<option value="">Select Branch</option>';
            data.data.forEach(branch => {
                branchSelect.innerHTML += `<option value="${branch.id}">${branch.name}</option>`;
            });
            branchSelect.disabled = false;
        })
        .catch(() => {
            branchSelect.innerHTML = '<option value="">Error loading branches</option>';
        });
});

document.getElementById('branch_id').addEventListener('change', function() {
    const continueBtn = document.getElementById('continueBtn');
    continueBtn.disabled = !this.value;
});
</script>
@endpush
