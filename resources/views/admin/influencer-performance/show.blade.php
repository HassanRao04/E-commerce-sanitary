@extends('layouts.admin')

@section('title', $influencer->name.' — Influencer')

@section('content')
    @php
        $currency = config('shop.currency_symbol');
        $exportQuery = array_filter([
            'from' => $filters['from'],
            'to' => $filters['to'],
            'coupon_id' => $filters['coupon_id'],
            'status' => $filters['status'],
        ], fn ($value) => filled($value));
    @endphp

    <div class="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
        <div>
            <a href="{{ route('admin.influencer-performance.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800">← Influencer Performance</a>
            <h2 class="mt-1 text-2xl font-bold text-gray-900">{{ $influencer->name }}</h2>
            <p class="text-sm text-gray-500">Influencer detail, coupons, orders, and commission.</p>
        </div>

        <div class="flex flex-wrap gap-2">
            <a href="{{ route('admin.influencer-performance.export', array_merge(['influencer' => $influencer, 'format' => 'csv'], $exportQuery)) }}"
               class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">CSV</a>
            <a href="{{ route('admin.influencer-performance.export', array_merge(['influencer' => $influencer, 'format' => 'excel'], $exportQuery)) }}"
               class="rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Excel</a>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
        @include('admin.partials.stat-card', [
            'label' => 'Wallet Balance',
            'value' => $currency.' '.number_format($wallet['balance'], 2),
            'hint' => 'Credits − payouts (ledger)',
            'tone' => 'emerald',
            'icon' => 'credit-card',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Total Sales',
            'value' => $currency.' '.number_format($summary['total_sales'], 2),
            'hint' => number_format($summary['total_orders']).' orders',
            'tone' => 'emerald',
            'icon' => 'shopping-cart',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Total Commission',
            'value' => $currency.' '.number_format($summary['total_commission'], 2),
            'hint' => 'Pending '.$currency.' '.number_format($summary['pending_commission'], 2),
            'tone' => 'violet',
            'icon' => 'chart-bar',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Paid Commission',
            'value' => $currency.' '.number_format($summary['paid_commission'], 2),
            'tone' => 'blue',
            'icon' => 'credit-card',
        ])
        @include('admin.partials.stat-card', [
            'label' => 'Customers',
            'value' => number_format($summary['customers_count']),
            'hint' => 'Discount '.$currency.' '.number_format($summary['total_discount'], 2),
            'tone' => 'amber',
            'icon' => 'users',
        ])
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-6 text-sm space-y-3">
            <h3 class="text-base font-semibold text-gray-900">Profile</h3>
            <p><span class="text-gray-500">Name:</span> {{ $influencer->name }}</p>
            <p><span class="text-gray-500">Email:</span> {{ $influencer->email }}</p>
            <p><span class="text-gray-500">Phone:</span> {{ $influencer->phone ?? '—' }}</p>
            <p><span class="text-gray-500">Status:</span> {{ $influencer->status?->label() ?? '—' }}</p>
            <p><span class="text-gray-500">Coupons:</span> {{ $coupons->count() }}</p>
        </div>

        <div class="lg:col-span-2 bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Coupons</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Code</th>
                            <th class="px-4 py-3 text-left">Discount</th>
                            <th class="px-4 py-3 text-left">Commission</th>
                            <th class="px-4 py-3 text-left">Uses</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($coupons as $coupon)
                            <tr>
                                <td class="px-4 py-3 font-mono font-semibold">{{ $coupon->code }}</td>
                                <td class="px-4 py-3">{{ $coupon->formatted_value }}</td>
                                <td class="px-4 py-3">
                                    @if ($coupon->commission_enabled && $coupon->commission_type === \App\Enums\CouponType::Percent)
                                        {{ rtrim(rtrim(number_format((float) $coupon->commission_value, 2), '0'), '.') }}%
                                    @elseif ($coupon->commission_enabled && $coupon->commission_type === \App\Enums\CouponType::Fixed)
                                        {{ $currency }}{{ number_format((float) $coupon->commission_value, 2) }}
                                    @else
                                        —
                                    @endif
                                </td>
                                <td class="px-4 py-3">{{ $coupon->used_count }}@if($coupon->max_uses)/{{ $coupon->max_uses }}@endif</td>
                                <td class="px-4 py-3">
                                    <span class="rounded-full px-2 py-0.5 text-xs {{ $coupon->is_valid ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                        {{ $coupon->is_valid ? 'Active' : 'Inactive' }}
                                    </span>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No coupons assigned.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-6 grid grid-cols-1 gap-6 lg:grid-cols-3">
        @can('coupons.manage')
            <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-6">
                <h3 class="text-base font-semibold text-gray-900">Manual payout</h3>
                <p class="mt-1 text-xs text-gray-500">Records a debit on the commission ledger. Does not change order commission amounts.</p>
                <form method="POST" action="{{ route('admin.influencer-performance.payout', $influencer) }}" class="mt-4 space-y-3">
                    @csrf
                    <div>
                        <label for="payout_amount" class="block text-xs font-medium text-gray-500">Amount</label>
                        <input type="number" step="0.01" min="0.01" name="amount" id="payout_amount" value="{{ old('amount') }}" required
                               class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
                        @error('amount')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                    </div>
                    <div>
                        <label for="admin_notes" class="block text-xs font-medium text-gray-500">Payment note</label>
                        <textarea name="admin_notes" id="admin_notes" rows="2" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">{{ old('admin_notes', 'Manual payout') }}</textarea>
                    </div>
                    <div>
                        <label for="transaction_id" class="block text-xs font-medium text-gray-500">Transaction ID (optional)</label>
                        <input type="text" name="transaction_id" id="transaction_id" value="{{ old('transaction_id') }}" maxlength="191"
                               class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm" placeholder="Bank / gateway reference">
                    </div>
                    <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800"
                            onclick="return confirm('Record this payout debit on the ledger?');">
                        Record payout
                    </button>
                </form>
            </div>
        @endcan

        <div class="@can('coupons.manage') lg:col-span-2 @else lg:col-span-3 @endcan bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
            <div class="border-b border-gray-100 px-6 py-4">
                <h3 class="text-base font-semibold text-gray-900">Commission ledger</h3>
                <p class="mt-1 text-xs text-gray-500">Credits reference orders (amount not duplicated). Debits are payouts.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200 text-sm">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left">Date</th>
                            <th class="px-4 py-3 text-left">Type</th>
                            <th class="px-4 py-3 text-right">Credit</th>
                            <th class="px-4 py-3 text-right">Debit</th>
                            <th class="px-4 py-3 text-right">Running Balance</th>
                            <th class="px-4 py-3 text-left">Reference Order</th>
                            <th class="px-4 py-3 text-left">Coupon</th>
                            <th class="px-4 py-3 text-left">Admin Notes</th>
                            <th class="px-4 py-3 text-left">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse ($ledger as $row)
                            <tr>
                                <td class="px-4 py-3 whitespace-nowrap">{{ $row->date?->format('M j, Y H:i') }}</td>
                                <td class="px-4 py-3">{{ $row->type }}</td>
                                <td class="px-4 py-3 text-right text-emerald-700">
                                    @if ($row->credit > 0)+{{ $currency }}{{ number_format($row->credit, 2) }}@else —@endif
                                </td>
                                <td class="px-4 py-3 text-right text-red-600">
                                    @if ($row->debit > 0)−{{ $currency }}{{ number_format($row->debit, 2) }}@else —@endif
                                </td>
                                <td class="px-4 py-3 text-right font-semibold">{{ $currency }}{{ number_format($row->running_balance, 2) }}</td>
                                <td class="px-4 py-3">{{ $row->order_number ?? '—' }}</td>
                                <td class="px-4 py-3 font-mono">{{ $row->coupon_code ?? '—' }}</td>
                                <td class="px-4 py-3 text-gray-600">{{ $row->admin_notes ?? '—' }}</td>
                                <td class="px-4 py-3">{{ $row->status }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="9" class="px-4 py-6 text-center text-gray-500">No ledger transactions yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="mb-6 bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 p-4">
        <form method="GET" class="grid grid-cols-1 gap-4 md:grid-cols-6">
            <div>
                <label for="from" class="block text-xs font-medium text-gray-500">From</label>
                <input type="date" name="from" id="from" value="{{ $filters['from'] }}" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
            </div>
            <div>
                <label for="to" class="block text-xs font-medium text-gray-500">To</label>
                <input type="date" name="to" id="to" value="{{ $filters['to'] }}" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
            </div>
            <div>
                <label for="coupon_id" class="block text-xs font-medium text-gray-500">Coupon</label>
                <select name="coupon_id" id="coupon_id" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">All coupons</option>
                    @foreach ($couponOptions as $coupon)
                        <option value="{{ $coupon->id }}" @selected((string) $filters['coupon_id'] === (string) $coupon->id)>{{ $coupon->code }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label for="status" class="block text-xs font-medium text-gray-500">Status</label>
                <select name="status" id="status" class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm">
                    <option value="">All statuses</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->slug }}" @selected($filters['status'] === $status->slug)>{{ $status->name }}</option>
                    @endforeach
                </select>
            </div>
            <div class="md:col-span-2 flex items-end gap-2">
                <button type="submit" class="rounded-md bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-800">Apply filters</button>
                <a href="{{ route('admin.influencer-performance.show', $influencer) }}" class="rounded-md border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50">Reset</a>
            </div>
        </form>
    </div>

    <div class="mb-6 bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4">
            <h3 class="text-base font-semibold text-gray-900">Customers</h3>
        </div>
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-right">Orders</th>
                        <th class="px-4 py-3 text-right">Sales</th>
                        <th class="px-4 py-3 text-right">Commission</th>
                        <th class="px-4 py-3 text-left">Last Order</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($customers as $customer)
                        <tr>
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ $customer->customer_name }}</div>
                                <div class="text-xs text-gray-500">{{ $customer->customer_email }}</div>
                            </td>
                            <td class="px-4 py-3 text-right">{{ number_format($customer->orders_count) }}</td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$customer->total_sales" /></td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$customer->total_commission" /></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $customer->last_order_at ? \Illuminate\Support\Carbon::parse($customer->last_order_at)->format('M j, Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-6 text-center text-gray-500">No customers for these filters.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <div class="border-b border-gray-100 px-6 py-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h3 class="text-base font-semibold text-gray-900">Orders</h3>
            @can('coupons.manage')
                @if ($orders->contains(fn ($order) => ! $order->influencer_commission_paid_at))
                    <button
                        type="submit"
                        form="bulk-mark-paid-form"
                        class="inline-flex items-center rounded-md bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-700"
                        onclick="return confirm('Mark selected commissions as paid?');"
                    >
                        Mark Selected as Paid
                    </button>
                @endif
            @endcan
        </div>
        @can('coupons.manage')
            <form method="POST" action="{{ route('admin.influencer-performance.mark-selected-paid', $influencer) }}" id="bulk-mark-paid-form">
                @csrf
            </form>
        @endcan
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        @can('coupons.manage')
                            <th class="px-4 py-3 w-10">
                                <input
                                    type="checkbox"
                                    class="rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                    onclick="document.querySelectorAll('.commission-row-checkbox').forEach(cb => cb.checked = this.checked)"
                                    aria-label="Select all pending commissions"
                                >
                            </th>
                        @endcan
                        <th class="px-4 py-3 text-left">Order</th>
                        <th class="px-4 py-3 text-left">Customer</th>
                        <th class="px-4 py-3 text-left">Coupon</th>
                        <th class="px-4 py-3 text-left">Status</th>
                        <th class="px-4 py-3 text-right">Sales</th>
                        <th class="px-4 py-3 text-right">Commission</th>
                        <th class="px-4 py-3 text-left">Date</th>
                        <th class="px-4 py-3 text-right">Actions</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($orders as $order)
                        <tr>
                            @can('coupons.manage')
                                <td class="px-4 py-3">
                                    @if (! $order->influencer_commission_paid_at)
                                        <input
                                            type="checkbox"
                                            name="order_ids[]"
                                            value="{{ $order->id }}"
                                            form="bulk-mark-paid-form"
                                            class="commission-row-checkbox rounded border-gray-300 text-slate-900 focus:ring-slate-500"
                                            aria-label="Select order {{ $order->order_number }}"
                                        >
                                    @endif
                                </td>
                            @endcan
                            <td class="px-4 py-3 font-medium">{{ $order->order_number }}</td>
                            <td class="px-4 py-3">
                                <div>{{ $order->customer_name }}</div>
                                <div class="text-xs text-gray-500">{{ $order->customer_email }}</div>
                            </td>
                            <td class="px-4 py-3 font-mono">{{ $order->trackedCoupon?->code ?? $order->coupon_code ?? '—' }}</td>
                            <td class="px-4 py-3"><x-order-status-badge :status="$order->status" /></td>
                            <td class="px-4 py-3 text-right">{{ $order->formatted_grand_total }}</td>
                            <td class="px-4 py-3 text-right">
                                <div><x-money :amount="$order->influencer_commission_amount" /></div>
                                <div class="text-xs {{ $order->influencer_commission_paid_at ? 'text-green-600' : 'text-amber-600' }}">
                                    {{ $order->influencer_commission_paid_at ? 'Paid' : 'Pending' }}
                                </div>
                            </td>
                            <td class="px-4 py-3 whitespace-nowrap text-gray-600">{{ $order->created_at?->format('M j, Y') }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="inline-flex flex-wrap items-center justify-end gap-x-3 gap-y-1">
                                    @can('view', $order)
                                        <a href="{{ route('admin.orders.show', $order) }}" class="text-slate-700 hover:underline">View</a>
                                    @endcan
                                    @can('coupons.manage')
                                        @if (! $order->influencer_commission_paid_at)
                                            <details class="relative text-left">
                                                <summary class="cursor-pointer list-none text-indigo-600 hover:text-indigo-800 [&::-webkit-details-marker]:hidden">
                                                    Pay Commission
                                                </summary>
                                                <div class="absolute right-0 z-20 mt-2 w-72 rounded-lg border border-gray-200 bg-white p-3 shadow-lg">
                                                    <form method="POST" action="{{ route('admin.influencer-performance.pay-commission', [$influencer, $order]) }}" class="space-y-2">
                                                        @csrf
                                                        <p class="text-xs text-gray-500">{{ $order->order_number }} · ledger debit + mark paid</p>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500">Payout amount</label>
                                                            <input
                                                                type="number"
                                                                step="0.01"
                                                                min="0.01"
                                                                name="amount"
                                                                value="{{ old('amount', number_format((float) $order->influencer_commission_amount, 2, '.', '')) }}"
                                                                required
                                                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500">Note (optional)</label>
                                                            <input
                                                                type="text"
                                                                name="admin_notes"
                                                                value="{{ old('admin_notes') }}"
                                                                placeholder="Pay commission {{ $order->order_number }}"
                                                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"
                                                            >
                                                        </div>
                                                        <div>
                                                            <label class="block text-xs font-medium text-gray-500">Transaction ID (optional)</label>
                                                            <input
                                                                type="text"
                                                                name="transaction_id"
                                                                value="{{ old('transaction_id') }}"
                                                                maxlength="191"
                                                                class="mt-1 w-full rounded-md border-gray-300 text-sm shadow-sm"
                                                                placeholder="Bank / gateway reference"
                                                            >
                                                        </div>
                                                        <button type="submit" class="w-full rounded-md bg-indigo-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-indigo-700">
                                                            Save payout
                                                        </button>
                                                    </form>
                                                </div>
                                            </details>
                                            <form method="POST" action="{{ route('admin.influencer-performance.mark-paid', [$influencer, $order]) }}" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <button type="submit" class="text-emerald-600 hover:text-emerald-800">Mark as Paid</button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ auth()->user()?->can('coupons.manage') ? 9 : 8 }}" class="px-4 py-6 text-center text-gray-500">
                                No orders for these filters.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if ($orders->hasPages())
            <div class="border-t px-4 py-3">{{ $orders->links() }}</div>
        @endif
    </div>
@endsection
