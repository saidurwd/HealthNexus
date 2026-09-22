@extends('layouts.adminlte')

@section('page_title', 'About')

@section('page_content')
<div class="card">
    <div class="card-header"><h3 class="card-title">About This System</h3></div>
    <div class="card-body p-0">
        <table class="table table-striped mb-0">
            <tbody>
                @foreach($info as $key => $value)
                    <tr>
                        <th style="width: 260px;">{{ ucwords(str_replace('_', ' ', $key)) }}</th>
                        <td>
                            @if(is_bool($value))
                                <span class="badge {{ $value ? 'bg-warning' : 'bg-secondary' }}">{{ $value ? 'Yes' : 'No' }}</span>
                            @else
                                {{ $value ?? '-' }}
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@stop
