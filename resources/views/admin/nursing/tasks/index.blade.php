@extends('layouts.adminlte')

@section('page_title', 'Nursing Tasks')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Tasks') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3">
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">{{ __('All Statuses') }}</option>
                    @foreach(['pending','in_progress','completed','skipped','refused','cancelled','overdue'] as $status)
                        <option value="{{ $status }}" {{ request('status') == $status ? 'selected' : '' }}>{{ ucfirst(str_replace('_',' ',$status)) }}</option>
                    @endforeach
                </select>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Patient') }}</th><th>{{ __('Task') }}</th><th>{{ __('Due') }}</th><th>{{ __('Nurse') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($tasks as $task)
                        <tr>
                            <td>{{ $task->patient?->full_name }}</td>
                            <td>{{ $task->task_type }}</td>
                            <td>{{ $task->due_at }}</td>
                            <td>{{ $task->assignedNurse?->name ?? '—' }}</td>
                            <td><span class="badge bg-info">{{ ucfirst(str_replace('_',' ',$task->status)) }}</span></td>
                            <td>
                                @if(in_array($task->status, ['pending','in_progress','overdue']))
                                    @can('nursing.task.complete')
                                        <form action="{{ route('admin.nursing.tasks.complete', $task) }}" method="post" class="d-inline">@csrf<button class="btn btn-xs btn-success">{{ __('Complete') }}</button></form>
                                    @endcan
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="6" class="text-center">{{ __('No tasks found.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $tasks->links() }}</div>
    </div>
@stop
