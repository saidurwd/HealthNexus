@extends('adminlte::auth.auth-page', ['authType' => 'login'])

@section('auth_body')
    <form action="{{ route('login.context') }}" method="post">
        @csrf

        <div class="mb-3">
            <label for="organization_id" class="form-label">{{ __('Organization') }}</label>
            <select name="organization_id" id="organization_id" class="form-control @error('organization_id') is-invalid @enderror" required>
                <option value="">{{ __('Select Organization') }}</option>
                @foreach($organizations as $organization)
                    <option value="{{ $organization->id }}" data-branches='@json($organization->branches()->get(["id", "name", "code"]))' {{ old('organization_id') == $organization->id ? 'selected' : '' }}>
                        {{ $organization->name }} ({{ $organization->code }})
                    </option>
                @endforeach
            </select>
            @error('organization_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="mb-3">
            <label for="branch_id" class="form-label">{{ __('Branch') }}</label>
            <select name="branch_id" id="branch_id" class="form-control @error('branch_id') is-invalid @enderror" required {{ old('organization_id') ? '' : 'disabled' }}>
                <option value="">{{ __('Select Branch') }}</option>
            </select>
            @error('branch_id')
                <span class="invalid-feedback">{{ $message }}</span>
            @enderror
        </div>

        <div class="row">
            <div class="col-7">
                <a href="{{ route('login') }}" class="btn btn-secondary">
                    {{ __('Back') }}
                </a>
            </div>

            <div class="col-5">
                <div class="d-grid">
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-box-arrow-in-right me-1"></i>
                        {{ __('Sign in') }}
                    </button>
                </div>
            </div>
        </div>
    </form>

    <script>
        document.getElementById('organization_id').addEventListener('change', function() {
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
            const organizationSelect = document.getElementById('organization_id');
            if (organizationSelect.value) {
                organizationSelect.dispatchEvent(new Event('change'));
            }
        });
    </script>
@stop
