@extends('layouts.adminlte')

@section('page_title', 'Worklist')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ __('Worklist') }}</h3></div>
        <div class="card-body p-0">
            <form method="GET" class="p-3 row g-2">
                <div class="col-md-3">
                    <select name="section_id" class="form-control">
                        <option value="">{{ __('All Sections') }}</option>
                        @foreach($sections as $section)
                            <option value="{{ $section->id }}" {{ request('section_id') == $section->id ? 'selected' : '' }}>{{ $section->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="priority" class="form-control">
                        <option value="">{{ __('All Priorities') }}</option>
                        @foreach(['stat','urgent','routine'] as $priority)
                            <option value="{{ $priority }}" {{ request('priority') == $priority ? 'selected' : '' }}>{{ ucfirst($priority) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2"><button type="submit" class="btn btn-primary">{{ __('Filter') }}</button></div>
            </form>
            <table class="table table-striped">
                <thead><tr><th>{{ __('Order') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Test') }}</th><th>{{ __('Section') }}</th><th>{{ __('Priority') }}</th><th>{{ __('Status') }}</th><th></th></tr></thead>
                <tbody>
                    @forelse($items as $item)
                        <tr>
                            <td>{{ $item->labOrder->order_number }}</td>
                            <td>{{ $item->labOrder->patient?->full_name }}</td>
                            <td>{{ $item->test?->name ?? $item->requested_test_name }}</td>
                            <td>{{ $item->test?->section?->name }}</td>
                            <td><span class="badge {{ $item->priority === 'stat' ? 'bg-danger' : ($item->priority === 'urgent' ? 'bg-warning' : 'bg-secondary') }}">{{ ucfirst($item->priority) }}</span></td>
                            <td>{{ ucfirst(str_replace('_', ' ', $item->status)) }}</td>
                            <td><a href="{{ route('admin.lab.orders.show', $item->labOrder) }}" class="btn btn-xs btn-info">{{ __('Open Order') }}</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center">{{ __('Worklist is empty.') }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="card-footer">{{ $items->links() }}</div>
    </div>
@stop
