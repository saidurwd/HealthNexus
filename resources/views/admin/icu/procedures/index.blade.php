@extends('layouts.adminlte')

@section('page_title', 'ICU Procedures')

@section('page_content')
    <a href="{{ route('admin.icu.procedures.create') }}" class="btn btn-primary mb-2">{{ __('New Procedure') }}</a>

    <div class="card">
        <div class="card-body p-0">
            <table class="table table-striped">
                <thead><tr><th>{{ __('Code') }}</th><th>{{ __('Name') }}</th><th>{{ __('Category') }}</th></tr></thead>
                <tbody>
                    @forelse($procedures as $procedure)
                        <tr>
                            <td>{{ $procedure->code }}</td>
                            <td>{{ $procedure->name }}</td>
                            <td>{{ $procedure->category }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="text-center">{{ __('No procedures configured.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ $procedures->links() }}
@stop
