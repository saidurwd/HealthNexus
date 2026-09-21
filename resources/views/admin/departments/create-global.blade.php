@extends('layouts.adminlte')

@section('page_title', 'Create Department')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Select Company and Branch</h3>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.companies.branches.departments.create') }}" id="departmentCreateForm">
                <div class="mb-3">
                    <label for="company_id" class="form-label">Company</label>
                    <select class="form-control" id="company_id" name="company" required>
                        <option value="">Select Company</option>
                        @foreach ($companies as $company)
                            <option value="{{ $company->id }}">{{ $company->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label for="branch_id" class="form-label">Branch</label>
                    <select class="form-control" id="branch_id" name="branch" required disabled>
                        <option value="">Select Company First</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary" id="continueBtn" disabled>Continue</button>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
<script>
document.getElementById('company_id').addEventListener('change', function() {
    const companyId = this.value;
    const branchSelect = document.getElementById('branch_id');
    const continueBtn = document.getElementById('continueBtn');

    if (!companyId) {
        branchSelect.innerHTML = '<option value="">Select Company First</option>';
        branchSelect.disabled = true;
        continueBtn.disabled = true;
        return;
    }

    fetch(`/api/v1/companies/${companyId}/branches`)
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
