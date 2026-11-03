@extends('layouts.adminlte')

@section('page_title', __('Money received'))

@section('page_content')
    @include('admin.welfare._flash')
    <x-ui.dashboard :question="__('Which gifts make up the amount received?')" :period="$period" :periods="['7d', '30d', '90d', 'month', 'last_month', 'custom']">
        <p class="small text-muted" data-list-total="{{ number_format((float) $total, 2, '.', '') }}" data-list-count="{{ $rows->total() }}">{{ __(':n gifts', ['n' => \App\Support\Dashboard\Numbers::digits((string) $rows->total())]) }} · ৳ {{ \App\Support\Dashboard\Numbers::number($total, 2) }}</p>
        <div class="card"><div class="table-responsive"><table class="table table-sm mb-0"><thead><tr><th>{{ __('When') }}</th><th>{{ __('Receipt') }}</th><th>{{ __('Fund') }}</th><th>{{ __('Donor') }}</th><th class="text-end">{{ __('Amount') }}</th></tr></thead><tbody>
            @forelse($rows as $e)<tr><td>{{ $e->occurred_at?->timezone('Asia/Dhaka')->format('d M Y H:i') }}</td><td>{{ $e->receipt_no }}</td><td>{{ $e->fund?->name }}</td><td>{{ $e->donor ? ($e->donor->anonymous ? __('Anonymous') : $e->donor->name) : '' }}</td><td class="text-end">{{ \App\Support\Dashboard\Numbers::number($e->amount, 2) }}</td></tr>@empty<tr><td colspan="5"><x-ui.empty-state :title="__('Nothing here.')" :hint="__('Try a longer period.')" /></td></tr>@endforelse</tbody></table></div><div class="card-footer">{{ $rows->links() }}</div></div>
    </x-ui.dashboard>
@stop
