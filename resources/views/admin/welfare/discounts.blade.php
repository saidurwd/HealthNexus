@extends('layouts.adminlte')

@section('page_title', $status === 'requested' ? __('Discounts waiting for Billing') : __('Discounts given to patients'))

@section('page_content')
    @include('admin.welfare._flash')
    <x-ui.dashboard :question="$status === 'requested' ? __('Which welfare discounts has Billing not approved yet?') : __('Which discounts make up the amount given?')" :period="$status === 'requested' ? null : $period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']">
        <p class="small text-muted" data-list-total="{{ number_format((float) $total, 2, '.', '') }}" data-list-count="{{ $rows->total() }}">{{ __(':n discounts', ['n' => \App\Support\Dashboard\Numbers::digits((string) $rows->total())]) }} · ৳ {{ \App\Support\Dashboard\Numbers::number($total, 2) }} · {{ __(':n patients', ['n' => \App\Support\Dashboard\Numbers::digits((string) $patients)]) }}</p>
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Grant') }}</th><th>{{ __('Fund') }}</th><th>{{ __('Patient') }}</th><th>{{ __('Bill') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead><tbody>
            @forelse($rows as $a)<tr><td>{{ ($a->settled_at ?? $a->requested_at)?->timezone('Asia/Dhaka')->format('d M Y') }}</td><td><a href="{{ route('admin.welfare.grants.show', $a->grant->public_id) }}">{{ $a->grant->number }}</a></td><td>{{ $a->grant->fund?->name }}</td><td>{{ $a->patient?->display_name }} <span class="text-muted">{{ $a->patient?->enterprise_patient_no }}</span></td><td>{{ $a->invoice?->invoice_number }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($a->amount, 2) }}</td></tr>@empty<tr><td colspan="6"><x-ui.empty-state :title="__('Nothing here.')" :hint="__('Try a longer period.')" /></td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    </x-ui.dashboard>
@stop
