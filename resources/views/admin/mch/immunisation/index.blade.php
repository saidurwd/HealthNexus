@extends('layouts.adminlte')

@section('page_title', __('Immunisation'))

@section('page_content')
    @include('admin.mch._flash')
    <p class="text-muted">{{ __('Children with a vaccine due now or overdue, those most overdue first. The schedule is the hospital\'s setting: check it with the national immunisation programme.') }}</p>
    <div class="card"><div class="table-responsive"><table class="table table-sm mb-0" data-list-count="{{ $total }}">
        <thead><tr><th>{{ __('Child') }}</th><th>{{ __('Born') }}</th><th class="text-end">{{ __('Due') }}</th><th class="text-end">{{ __('Overdue') }}</th><th></th></tr></thead><tbody>
        @forelse($rows as $r)@php $c = $names[$r->patient_id] ?? null; @endphp
            <tr><td>{{ $c?->full_name }} <small class="text-muted">{{ $c?->enterprise_patient_no }}</small></td><td>{{ $c ? \Carbon\CarbonImmutable::parse($c->date_of_birth)->format('d M Y') : '' }}</td><td class="text-end">{{ $r->due }}</td><td class="text-end {{ $r->overdue ? 'text-danger fw-bold' : '' }}">{{ $r->overdue }}</td><td><a class="btn btn-sm btn-outline-primary" href="{{ route('admin.mch.immunisation.card', $r->patient_id) }}">{{ __('Open the card') }}</a></td></tr>
        @empty<tr><td colspan="5"><x-ui.empty-state :title="__('No child has a vaccine due.')" icon="bi-shield-check" /></td></tr>@endforelse
        </tbody></table></div></div>
@stop
