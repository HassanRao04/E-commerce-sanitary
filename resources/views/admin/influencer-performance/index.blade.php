@extends('layouts.admin')

@section('title', 'Influencer Performance')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Influencer Performance',
    ])

    <p class="mb-4 text-sm text-gray-500">Sales and commission from influencer coupons, based on tracked order data.</p>

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200 text-sm">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Influencer Name</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Coupon</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total Orders</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total Sales</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total Discount Given</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Total Commission</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Pending Commission</th>
                        <th class="px-4 py-3 text-right whitespace-nowrap">Paid Commission</th>
                        <th class="px-4 py-3 text-left whitespace-nowrap">Last Order Date</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse ($rows as $row)
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">
                                <a href="{{ route('admin.influencer-performance.show', $row->influencer_id) }}" class="text-indigo-600 hover:text-indigo-800 hover:underline">
                                    {{ $row->influencer_name }}
                                </a>
                            </td>
                            <td class="px-4 py-3 font-mono font-semibold">{{ $row->coupon_code }}</td>
                            <td class="px-4 py-3 text-right">{{ number_format((int) $row->total_orders) }}</td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row->total_sales" /></td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row->total_discount" /></td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row->total_commission" /></td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row->pending_commission" /></td>
                            <td class="px-4 py-3 text-right"><x-money :amount="$row->paid_commission" /></td>
                            <td class="px-4 py-3 whitespace-nowrap">
                                {{ $row->last_order_at ? \Illuminate\Support\Carbon::parse($row->last_order_at)->format('M j, Y') : '—' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="px-4 py-8 text-center text-gray-500">
                                No influencer coupon orders tracked yet.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection
