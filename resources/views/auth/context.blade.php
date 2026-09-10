@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_body')
    <form action="{{ route('login.context') }}" method="post">
        @csrf

        <div class="mb-3">
            <label for="company_id" class="form-label">Company</label>
            <select name="company_id" id="company_id" class="form-control @error('company_id') is-invalid @enderror" required>
                <option value="">Select Company</option>
                @foreach($companies as $company)
                    <option value="{{ $company->id }}" data-branches='@json($company->branches()->get(["id", "name", "code"]))' {{ old('company_id') == $company->id ? 'selected' : '' }}>
                        {{ $company->name }} ({{ $company->code }})
                    </option>
                @endforeach
            </select>
            @error('company_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="branch_id" class="form-label">Branch</label>
            <select name="branch_id" id="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required {{ old('company_id') ? '' : 'disabled' }}>
                <option value="">Select Branch</option>
            </select>
            @error('branch_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <a href="{{ route('login') }}" class="btn btn-secondary">
                    Back
                </a>
            </div>

            <div class="col-5">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        Sign in
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('company_id').addEventListener('change', function() {
            const branchSelect = document.getElementById('branch_id');
            const selectedOption = this.options[this.selectedIndex];
            const branches = selectedOption.dataset.branches ? JSON.parse(selectedOption.dataset.branches) : [];
            
            branchSelect.innerHTML = '<option value="">Select Branch</option>';
            
            if (branches.length > 0) {
                branches.forEach(branch => {
                    const option = document.createElement('option');
                    option.value = branch.id;
                    option.textContent = branch.name + ' (' + branch.code + ')';
                    branchSelect.appendChild(option);
                });
                branchSelect.disabled = false;
            } else {
                branchSelect.disabled = true;
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const companySelect = document.getElementById('company_id');
            if (companySelect.value) {
                companySelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
@stop
