@extends('layouts.adminlte')

@section('page_title', 'Handover')

@section('page_content')
    <div class="card">
        <div class="card-header"><h3 class="card-title">{{ $handover->episode?->patient?->full_name }} — {{ ucfirst(str_replace('_',' ',$handover->status)) }}</h3></div>
        <div class="card-body">
            <dl class="row">
                @foreach($handover->items as $item)
                    <dt class="col-sm-3">{{ ucfirst(str_replace('_',' ',$item->section)) }}</dt>
                    <dd class="col-sm-9">{{ $item->content }}</dd>
                @endforeach
            </dl>
            @if($handover->status === 'draft')
                @can('nursing.handover.create')
                    <form action="{{ route('admin.nursing.handover.finalize', $handover) }}" method="post">
                        @csrf
                        <input type="number" name="incoming_nurse_id" class="form-control mb-2" placeholder="Incoming nurse user ID">
                        <button class="btn btn-primary">Finalize &amp; Send</button>
                    </form>
                @endcan
            @elseif($handover->status === 'pending_acknowledgement')
                @can('nursing.handover.acknowledge')
                    <form action="{{ route('admin.nursing.handover.acknowledge', $handover) }}" method="post">@csrf<button class="btn btn-success">Acknowledge</button></form>
                @endcan
            @endif
        </div>
    </div>
@stop
