@extends('layouts.storefront')

@section('title', 'My Orders — Influencer — '.config('app.name'))
@section('meta_description', 'Attributed orders and commissions for your influencer coupons.')

@section('content')
    <div class="ds-container ds-section">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('influencer.dashboard') }}" class="ds-link ds-body-sm">← Influencer Dashboard</a>
                <h1 class="ds-heading-2 mt-1">Orders</h1>
                <p class="ds-body mt-2 text-ink-600">Orders attributed to your influencer coupons.</p>
            </div>
        </div>

        <div class="mb-6 ds-card ds-card-body">
            <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-6">
                <div>
                    <label for="from" class="ds-body-sm text-ink-500">From</label>
                    <input type="date" name="from" id="from" value="{{ $filters['from'] }}" class="mt-1 w-full rounded-ds border border-ink-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="to" class="ds-body-sm text-ink-500">To</label>
                    <input type="date" name="to" id="to" value="{{ $filters['to'] }}" class="mt-1 w-full rounded-ds border border-ink-200 px-3 py-2 text-sm">
                </div>
                <div>
                    <label for="status" class="ds-body-sm text-ink-500">Status</label>
                    <select name="status" id="status" class="mt-1 w-full rounded-ds border border-ink-200 px-3 py-2 text-sm">
                        <option value="">All statuses</option>
                        @foreach ($statuses as $status)
                            <option value="{{ $status->slug }}" @selected($filters['status'] === $status->slug)>{{ $status->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="coupon_id" class="ds-body-sm text-ink-500">Coupon</label>
                    <select name="coupon_id" id="coupon_id" class="mt-1 w-full rounded-ds border border-ink-200 px-3 py-2 text-sm">
                        <option value="">All coupons</option>
                        @foreach ($couponOptions as $coupon)
                            <option value="{{ $coupon->id }}" @selected((string) $filters['coupon_id'] === (string) $coupon->id)>{{ $coupon->code }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-2">
                    <label for="search" class="ds-body-sm text-ink-500">Search</label>
                    <input type="search" name="search" id="search" value="{{ $filters['search'] }}" placeholder="Order #, customer, coupon…" class="mt-1 w-full rounded-ds border border-ink-200 px-3 py-2 text-sm">
                </div>
                <div class="md:col-span-6 flex flex-wrap items-center gap-2">
                    <button type="submit" class="ds-btn-primary">Apply filters</button>
                    <a href="{{ route('influencer.orders.index') }}" class="ds-btn-secondary">Reset</a>
                </div>
            </form>
        </div>

        <div class="ds-card overflow-hidden">
            <div class="overflow-x-auto">
                <table class="w-full ds-body-sm">
                    <thead class="bg-surface-muted text-left text-ink-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Order Number</th>
                            <th class="px-5 py-3 font-medium">Coupon</th>
                            <th class="px-5 py-3 font-medium text-right">Order Total</th>
                            <th class="px-5 py-3 font-medium text-right">Commission</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Paid / Pending</th>
                            <th class="px-5 py-3 font-medium">Date</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($orders as $order)
                            <tr>
                                <td class="px-5 py-4 font-medium text-ink">{{ $order->order_number }}</td>
                                <td class="px-5 py-4 font-mono">{{ $order->trackedCoupon?->code ?? $order->coupon_code ?? '—' }}</td>
                                <td class="px-5 py-4 text-right font-medium"><x-money :amount="$order->grand_total" /></td>
                                <td class="px-5 py-4 text-right font-medium"><x-money :amount="$order->influencer_commission_amount" /></td>
                                <td class="px-5 py-4"><x-order-status-badge :status="$order->status" /></td>
                                <td class="px-5 py-4">
                                    @if ($order->influencer_commission_paid_at)
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">Paid</span>
                                    @else
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">Pending</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 whitespace-nowrap text-ink-600">{{ $order->created_at?->format('M j, Y') }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-ink-500">No orders match these filters.</td>
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
