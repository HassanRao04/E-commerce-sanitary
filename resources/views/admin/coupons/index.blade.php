@extends('layouts.admin')

@section('title', 'Coupons')

@section('content')
    @include('admin.partials.page-header', [
        'title' => 'Coupons',
        'permission' => 'coupons.manage',
        'actionRoute' => route('admin.coupons.create'),
    ])

    <div class="bg-white rounded-xl shadow-sm ring-1 ring-gray-200/60 overflow-hidden">
        <table class="min-w-full divide-y divide-gray-200 text-sm">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left">Code</th>
                    <th class="px-4 py-3 text-left">Type</th>
                    <th class="px-4 py-3 text-left">Value</th>
                    <th class="px-4 py-3 text-left">Influencer</th>
                    <th class="px-4 py-3 text-left">Commission</th>
                    <th class="px-4 py-3 text-left">Uses</th>
                    <th class="px-4 py-3 text-left">Status</th>
                    <th class="px-4 py-3 text-left">Validity</th>
                    <th class="px-4 py-3 text-right">Actions</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-100">
                @forelse ($coupons as $coupon)
                    @php
                        $validityKey = match (true) {
                            $coupon->is_valid => 'active',
                            ! $coupon->is_active => 'disabled',
                            $coupon->is_expired => 'expired',
                            $coupon->is_exhausted => 'usage_limit',
                            filled($coupon->starts_at) && $coupon->starts_at->isFuture() => 'scheduled',
                            default => 'disabled',
                        };
                        $validityLabel = match ($validityKey) {
                            'active' => 'Active',
                            'disabled' => 'Disabled',
                            'expired' => 'Expired',
                            'usage_limit' => 'Usage Limit Reached',
                            'scheduled' => 'Scheduled (Starts on '.$coupon->starts_at->format('M j, Y g:i A').')',
                            default => 'Disabled',
                        };
                        $validityTone = match ($validityKey) {
                            'active' => 'bg-green-100 text-green-800',
                            'scheduled' => 'bg-blue-100 text-blue-800',
                            'expired' => 'bg-red-100 text-red-800',
                            'usage_limit' => 'bg-amber-100 text-amber-800',
                            default => 'bg-gray-100 text-gray-600',
                        };
                    @endphp
                    <tr>
                        <td class="px-4 py-3 font-mono font-semibold">{{ $coupon->code }}</td>
                        <td class="px-4 py-3">{{ ucfirst($coupon->type->value) }}</td>
                        <td class="px-4 py-3">{{ $coupon->formatted_value }}</td>
                        <td class="px-4 py-3">{{ $coupon->influencer?->name ?? '—' }}</td>
                        <td class="px-4 py-3">
                            @if ($coupon->commission_enabled && $coupon->commission_type === \App\Enums\CouponType::Percent)
                                {{ rtrim(rtrim(number_format((float) $coupon->commission_value, 2), '0'), '.') }}%
                            @elseif ($coupon->commission_enabled && $coupon->commission_type === \App\Enums\CouponType::Fixed)
                                {{ config('shop.currency_symbol') }}{{ number_format((float) $coupon->commission_value, 2) }}
                            @else
                                —
                            @endif
                        </td>
                        <td class="px-4 py-3">{{ $coupon->used_count }}@if($coupon->max_uses)/{{ $coupon->max_uses }}@endif</td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $coupon->is_active ? 'bg-green-100 text-green-800' : 'bg-gray-100 text-gray-600' }}">
                                {{ $coupon->is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </td>
                        <td class="px-4 py-3">
                            <span class="rounded-full px-2 py-0.5 text-xs {{ $validityTone }}">
                                {{ $validityLabel }}
                            </span>
                        </td>
                        <td class="px-4 py-3 text-right space-x-3">
                            @can('update', $coupon)
                                <a href="{{ route('admin.coupons.edit', $coupon) }}" class="text-indigo-600 hover:text-indigo-800">Edit</a>
                            @endcan
                            @can('delete', $coupon)
                                <form method="POST" action="{{ route('admin.coupons.destroy', $coupon) }}" class="inline" onsubmit="return confirm('Delete this coupon?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-800">Delete</button>
                                </form>
                            @endcan
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="9" class="px-4 py-8 text-center text-gray-500">No coupons defined.</td></tr>
                @endforelse
            </tbody>
        </table>
        @if ($coupons->hasPages())
            <div class="px-4 py-3 border-t">{{ $coupons->links() }}</div>
        @endif
    </div>
@endsection
