@extends('layouts.adminlte')

@section('page_title', 'Nursing Notes')

@section('page_content')
    @can('nursing.notes.create')
    <div class="card">
        <div class="card-header"><h3 class="card-title">New Note</h3></div>
        <form action="{{ route('admin.nursing.notes.store', $episode) }}" method="post">
            @csrf
            <div class="card-body">
                <select name="note_type" class="form-control mb-2">
                    @foreach(['narrative','structured','shift','progress','procedure','education','handover'] as $t)<option value="{{ $t }}">{{ ucfirst($t) }}</option>@endforeach
                </select>
                <textarea name="content" class="form-control" rows="3" required></textarea>
            </div>
            <div class="card-footer"><button class="btn btn-primary">Save Draft</button></div>
        </form>
    </div>
    @endcan

    @foreach($notes as $note)
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">{{ ucfirst($note->note_type) }} — {{ $note->createdBy?->name }} ({{ $note->created_at }}) <span class="badge bg-info">{{ $note->status }}</span></h3>
            </div>
            <div class="card-body">
                <p>{{ $note->content }}</p>
                @foreach($note->amendments as $a)
                    <div class="border-top pt-2"><small>Addendum {{ $a->created_at }}:</small> {{ $a->content }}</div>
                @endforeach
                @if($note->status === 'draft')
                    @can('nursing.notes.finalize')
                        <form action="{{ route('admin.nursing.notes.finalize', $note) }}" method="post">@csrf<button class="btn btn-sm btn-success">Finalize &amp; Sign</button></form>
                    @endcan
                @else
                    @can('nursing.notes.amend')
                        <form action="{{ route('admin.nursing.notes.amend', $note) }}" method="post" class="mt-2">
                            @csrf
                            <input name="content" class="form-control mb-1" placeholder="Addendum" required>
                            <button class="btn btn-sm btn-secondary">Add Addendum</button>
                        </form>
                    @endcan
                @endif
            </div>
        </div>
    @endforeach
    {{ $notes->links() }}
@stop
