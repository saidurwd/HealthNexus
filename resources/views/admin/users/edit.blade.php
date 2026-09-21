@extends('layouts.adminlte')

@section('page_title', 'Edit User')

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">Edit User</h3>
        </div>
        <form action="{{ route('admin.users.update', $user) }}" method="post">
            @csrf
            @method('put')
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="name" class="form-label">Name</label>
                            <input type="text" name="name" id="name" value="{{ old('name', $user->name) }}" class="form-control @error('name') is-invalid @enderror" required>
                            @error('name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" name="email" id="email" value="{{ old('email', $user->email) }}" class="form-control @error('email') is-invalid @enderror" required>
                            @error('email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="phone" class="form-label">Phone</label>
                            <input type="text" name="phone" id="phone" value="{{ old('phone', $user->phone) }}" class="form-control @error('phone') is-invalid @enderror">
                            @error('phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="timezone" class="form-label">Timezone</label>
                            <input type="text" name="timezone" id="timezone" value="{{ old('timezone', $user->timezone) }}" class="form-control @error('timezone') is-invalid @enderror">
                            @error('timezone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <label for="locale" class="form-label">Locale</label>
                            <input type="text" name="locale" id="locale" value="{{ old('locale', $user->locale) }}" class="form-control @error('locale') is-invalid @enderror">
                            @error('locale') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                        <div class="mb-3">
                            <div class="form-check">
                                <input type="checkbox" name="is_active" id="is_active" value="1" {{ old('is_active', $user->is_active) ? 'checked' : '' }} class="form-check-input">
                                <label for="is_active" class="form-check-label">Active</label>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label class="form-label">Companies</label>
                            @foreach($companies as $company)
                                <div class="form-check">
                                    <input type="checkbox" name="companies[]" value="{{ $company->id }}" id="company_{{ $company->id }}" class="form-check-input" {{ in_array($company->id, $userCompanies) ? 'checked' : '' }}>
                                    <label for="company_{{ $company->id }}" class="form-check-label">{{ $company->name }} ({{ $company->code }})</label>
                                </div>
                            @endforeach
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Branches</label>
                            @foreach($branches as $branch)
                                <div class="form-check">
                                    <input type="checkbox" name="branches[]" value="{{ $branch->id }}" id="branch_{{ $branch->id }}" class="form-check-input" {{ in_array($branch->id, $userBranches) ? 'checked' : '' }}>
                                    <label for="branch_{{ $branch->id }}" class="form-check-label">{{ $branch->name }} ({{ $branch->company->name ?? '' }})</label>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer">
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">Cancel</a>
                <button type="submit" class="btn btn-primary">Update User</button>
            </div>
        </form>
    </div>
@stop
