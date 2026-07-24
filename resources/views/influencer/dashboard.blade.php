@extends('layouts.storefront')

@section('title', 'Influencer Dashboard — '.config('app.name'))
@section('meta_description', 'Your influencer sales and commission overview at '.config('app.name').'.')

@section('content')
    <div class="ds-container ds-section">
        <div class="mb-8 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="ds-heading-2">Influencer Dashboard</h1>
                <p class="ds-body mt-2 text-ink-600">Welcome back, {{ auth()->user()->name }}. Sales and commissions from your attributed orders.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('influencer.orders.index') }}" class="ds-btn-secondary">View orders</a>
                <a href="{{ route('influencer.wallet') }}" class="ds-btn-secondary">Wallet</a>
                <a href="{{ route('influencer.commissions.index') }}" class="ds-btn-secondary">Commissions</a>
            </div>
        </div>

        <section class="mb-8" aria-labelledby="commission-section-heading">
            <div class="mb-4">
                <h2 id="commission-section-heading" class="ds-heading-4">Commission</h2>
                <p class="ds-body-sm mt-1 text-ink-500">Wallet, earnings, and payout activity for your coupons only.</p>
            </div>

            <div class="mb-8 grid grid-cols-2 gap-4 lg:grid-cols-3 xl:grid-cols-4">
                <div class="ds-card ds-card-body ring-2 ring-ink/10">
                    <p class="ds-body-sm text-ink-500">Current Wallet Balance</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$wallet['balance']" /></p>
                    <p class="mt-1 text-xs text-ink-500">Credits − payouts</p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">Pending Commission</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$summary['pending_commission']" /></p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">Paid Commission</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$summary['paid_commission']" /></p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">Lifetime Earnings</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$summary['total_commission']" /></p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">Today's Earnings</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$summary['today_earnings']" /></p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">This Month Earnings</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$summary['this_month_earnings']" /></p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">Total Orders</p>
                    <p class="mt-1 text-2xl font-bold text-ink">{{ number_format($summary['total_orders']) }}</p>
                </div>
                <div class="ds-card ds-card-body">
                    <p class="ds-body-sm text-ink-500">Total Sales</p>
                    <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$summary['total_sales']" /></p>
                </div>
            </div>

            <div class="mb-8 ds-card overflow-hidden">
                <div class="border-b border-ink-100 px-5 py-4">
                    <h3 class="ds-heading-4">Coupon Usage</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full ds-body-sm">
                        <thead class="bg-surface-muted text-left text-ink-500">
                            <tr>
                                <th class="px-5 py-3 font-medium">Coupon</th>
                                <th class="px-5 py-3 font-medium">Orders</th>
                                <th class="px-5 py-3 font-medium text-right">Sales</th>
                                <th class="px-5 py-3 font-medium text-right">Commission</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-ink-100">
                            @forelse ($couponUsage as $row)
                                <tr>
                                    <td class="px-5 py-4 font-mono font-semibold text-ink">{{ $row->coupon_code }}</td>
                                    <td class="px-5 py-4">{{ number_format($row->total_orders) }}</td>
                                    <td class="px-5 py-4 text-right"><x-money :amount="$row->total_sales" /></td>
                                    <td class="px-5 py-4 text-right"><x-money :amount="$row->total_commission" /></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="px-5 py-6 text-center text-ink-500">No coupon usage yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
                <div class="ds-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                        <h3 class="ds-heading-4">Latest Orders</h3>
                        <a href="{{ route('influencer.orders.index') }}" class="ds-link ds-body-sm">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full ds-body-sm">
                            <thead class="bg-surface-muted text-left text-ink-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Order</th>
                                    <th class="px-5 py-3 font-medium hidden sm:table-cell">Date</th>
                                    <th class="px-5 py-3 font-medium">Coupon</th>
                                    <th class="px-5 py-3 font-medium text-right">Sales</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @forelse ($latestOrders as $order)
                                    <tr>
                                        <td class="px-5 py-4 font-medium text-ink">{{ $order->order_number }}</td>
                                        <td class="px-5 py-4 hidden sm:table-cell text-ink-600">{{ $order->created_at?->format('M j, Y') }}</td>
                                        <td class="px-5 py-4 font-mono">{{ $order->trackedCoupon?->code ?? $order->coupon_code ?? '—' }}</td>
                                        <td class="px-5 py-4 text-right font-medium"><x-money :amount="$order->grand_total" /></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-6 text-center text-ink-500">No attributed orders yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="ds-card overflow-hidden">
                    <div class="flex items-center justify-between border-b border-ink-100 px-5 py-4">
                        <h3 class="ds-heading-4">Latest Payouts</h3>
                        <a href="{{ route('influencer.wallet') }}#payout-history" class="ds-link ds-body-sm">View all</a>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full ds-body-sm">
                            <thead class="bg-surface-muted text-left text-ink-500">
                                <tr>
                                    <th class="px-5 py-3 font-medium">Date</th>
                                    <th class="px-5 py-3 font-medium text-right">Amount</th>
                                    <th class="px-5 py-3 font-medium">Order</th>
                                    <th class="px-5 py-3 font-medium">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-ink-100">
                                @forelse ($latestPayouts as $payout)
                                    <tr>
                                        <td class="px-5 py-4 text-ink-600">{{ $payout->date?->format('M j, Y') ?? '—' }}</td>
                                        <td class="px-5 py-4 text-right font-medium"><x-money :amount="$payout->amount" /></td>
                                        <td class="px-5 py-4 font-medium text-ink">{{ $payout->order_number ?? '—' }}</td>
                                        <td class="px-5 py-4">
                                            <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">{{ $payout->status }}</span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="px-5 py-6 text-center text-ink-500">No payouts yet.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </section>
    </div>
@endsection
