@extends('layouts.adminlte')

@section('page_title', __('ui_patients.title'))

@section('page_content')
    <div class="card">
        <div class="card-header">
            <h3 class="card-title">{{ __('ui_patients.title') }}</h3>
            <div class="card-tools">
                <a href="{{ route('admin.patients.create') }}" class="btn btn-primary btn-sm">{{ __('ui_patients.new_patient') }}</a>
            </div>
        </div>
        <div class="card-body p-0">
            <form method="GET" action="{{ route('admin.patients.index') }}" class="p-3">
                <div class="input-group">
                    <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="{{ __('ui_patients.search_placeholder') }}">
                    <button type="submit" class="btn btn-primary">{{ __('ui_core.search') }}</button>
                </div>
            </form>
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>{{ __('ui_patients.patient_no') }}</th>
                        <th>{{ __('ui_core.name') }}</th>
                        <th>{{ __('ui_patients.sex') }}</th>
                        <th>{{ __('ui_patients.phone') }}</th>
                        <th>{{ __('ui_core.status') }}</th>
                        <th>{{ __('ui_core.actions') }}</th>
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
                                    {{ __('ui_core.'.$patient->status) }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('admin.patients.show', $patient) }}" class="btn btn-xs btn-info">{{ __('ui_core.view') }}</a>
                                <a href="{{ route('admin.patients.edit', $patient) }}" class="btn btn-xs btn-warning">{{ __('ui_core.edit') }}</a>
                                <form action="{{ route('admin.patients.destroy', $patient) }}" method="post" class="d-inline" onsubmit="return confirm('{{ __('ui_core.confirm_delete') }}')">
                                    @csrf
                                    @method('delete')
                                    <button class="btn btn-xs btn-danger">{{ __('ui_core.delete') }}</button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">{{ __('ui_patients.no_patients_found') }}</td>
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
