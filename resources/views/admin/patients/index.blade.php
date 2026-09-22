@extends('layouts.adminlte')

@section('page_title', __('patients.title'))

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('patients.title') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-sm">{{ __('patients.new_patient') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" action="{{ route('admin.patients.index') }}" class="p-3">
                <div class="input-group">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('patients.search_placeholder') }}">
                    <button type="submit" class="btn btn-primary">{{ __('core.search') }}</button>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('patients.patient_no') }}</th>
                        <th>{{ __('core.name') }}</th>
                        <th>{{ __('patients.sex') }}</th>
                        <th>{{ __('patients.phone') }}</th>
                        <th>{{ __('core.status') }}</th>
                        <th>{{ __('core.actions') }}</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($patients as $patient)
                        <tr>
                            <td>{{ $patient->enterprise_patient_no }}</td>
                            <td>{{ $patient->full_name }}</td>
                            <td>{{ $patient->sex }}</td>
                            <td>{{ $patient->phone ?? '-' }}</td>
                            <td>
                                <span class="badge {{ $patient->status === 'active' ? 'bg-success' : 'bg-danger' }}">
                                    {{ __('core.'.$patient->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-xs btn-info">{{ __('core.view') }}</a>
                                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-xs btn-warning">{{ __('core.edit') }}</a>
                                <form action="{{ route('admin.patients.destroy', $patient) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('core.confirm_delete') }}')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">{{ __('core.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('patients.no_patients_found') }}</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">
            {{ $patients->links() }}
        </div>
    </div>
@stop
