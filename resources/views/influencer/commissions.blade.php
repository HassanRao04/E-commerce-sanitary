@extends('layouts.storefront')

@section('title', 'Commission History — Influencer — '.config('app.name'))
@section('meta_description', 'Commission history for your influencer attributed orders.')

@section('content')
    @php
        $exportQuery = array_filter([
            'from' => $filters['from'],
            'to' => $filters['to'],
            'coupon_id' => $filters['coupon_id'],
            'status' => $filters['status'],
            'search' => $filters['search'],
        ], fn ($value) => filled($value));
    @endphp

    <div class="ds-container ds-section">
        <div class="mb-6 flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('influencer.dashboard') }}" class="ds-link ds-body-sm">← Influencer Dashboard</a>
                <h1 class="ds-heading-2 mt-1">Commission History</h1>
                <p class="ds-body mt-2 text-ink-600">Built from your attributed orders. No separate commission records.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('influencer.commissions.export', array_merge(['format' => 'csv'], $exportQuery)) }}" class="ds-btn-secondary">CSV Export</a>
                <a href="{{ route('influencer.commissions.export', array_merge(['format' => 'excel'], $exportQuery)) }}" class="ds-btn-secondary">Excel Export</a>
            </div>
        </div>

        <div class="ds-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full ds-body-sm">
                    <thead class="bg-surface-muted text-left text-ink-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Order Number</th>
                            <th class="px-5 py-3 font-medium">Coupon</th>
                            <th class="px-5 py-3 font-medium text-right">Commission</th>
                            <th class="px-5 py-3 font-medium">Earned Date</th>
                            <th class="px-5 py-3 font-medium">Paid Date</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 font-mono">{{ $order->trackedCoupon?->code ?? $order->coupon_code ?? '—' }}</td>
                                <td class="px-5 py-4 text-right font-medium"><x-money :amount="$order->influencer_commission_amount" /></td>
                                <td class="px-5 py-4 whitespace-nowrap text-ink-600">{{ $order->created_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4 whitespace-nowrap text-ink-600">{{ $order->influencer_commission_paid_at?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($order->influencer_commission_paid_at)
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Paid</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Pending</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-8 text-center text-ink-500">No commission history yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($orders->hasPages())
                <div class="border-t border-ink-100 px-5 py-4">
                    {{ $orders->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
