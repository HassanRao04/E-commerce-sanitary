@extends('layouts.storefront')

@section('title', 'Wallet — Influencer — '.config('app.name'))
@section('meta_description', 'Your influencer wallet and commission ledger.')

@section('content')
    <div class="ds-container ds-section">
        <div class="mb-6 flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <a href="{{ route('influencer.dashboard') }}" class="ds-link ds-body-sm">← Influencer Dashboard</a>
                <h1 class="ds-heading-2 mt-1">Wallet &amp; Ledger</h1>
                <p class="ds-body mt-2 text-ink-600">Balance = order commission credits − payout debits. Credit amounts stay on orders.</p>
            </div>
            <a href="{{ route('influencer.commissions.index') }}" class="ds-btn-secondary">Commission history</a>
        </div>

        <div class="mb-8 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="ds-card ds-card-body ring-2 ring-ink/10">
                <p class="ds-body-sm text-ink-500">Wallet Balance</p>
                <p class="mt-1 text-3xl font-bold text-ink"><x-money :amount="$wallet['balance']" /></p>
            </div>
            <div class="ds-card ds-card-body">
                <p class="ds-body-sm text-ink-500">Pending commissions</p>
                <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$wallet['pending']" /></p>
            </div>
            <div class="ds-card ds-card-body">
                <p class="ds-body-sm text-ink-500">Paid commissions</p>
                <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$wallet['paid']" /></p>
            </div>
            <div class="ds-card ds-card-body">
                <p class="ds-body-sm text-ink-500">Total payouts</p>
                <p class="mt-1 text-2xl font-bold text-ink"><x-money :amount="$wallet['debits_total']" /></p>
            </div>
        </div>

        <div id="payout-history" class="mb-8 ds-card overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4">
                <h2 class="ds-heading-4">Payout History</h2>
                <p class="ds-body-sm mt-1 text-ink-500">Payouts only — not order or commission credit history.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full ds-body-sm">
                    <thead class="bg-surface-muted text-left text-ink-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium text-right">Amount</th>
                            <th class="px-5 py-3 font-medium">Reference</th>
                            <th class="px-5 py-3 font-medium">Admin</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                            <th class="px-5 py-3 font-medium">Payment Note</th>
                            <th class="px-5 py-3 font-medium">Transaction ID</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($payoutHistory as $payout)
                            <tr>
                                <td class="px-5 py-4 whitespace-nowrap text-ink-600">{{ $payout->date?->format('M j, Y') ?? '—' }}</td>
                                <td class="px-5 py-4 text-right font-medium text-ink"><x-money :amount="$payout->amount" /></td>
                                <td class="px-5 py-4 font-medium text-ink">{{ $payout->reference ?? '—' }}</td>
                                <td class="px-5 py-4 text-ink-600">{{ $payout->admin ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($payout->status === 'Completed' || $payout->status === 'Paid')
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">{{ $payout->status }}</span>
                                    @elseif ($payout->status === 'Pending')
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $payout->status }}</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $payout->status }}</span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-ink-600">{{ $payout->payment_note ?? '—' }}</td>
                                <td class="px-5 py-4 font-mono text-ink-600">{{ $payout->transaction_id ?? '—' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="px-5 py-8 text-center text-ink-500">No payouts yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="ds-card overflow-hidden">
            <div class="border-b border-ink-100 px-5 py-4">
                <h2 class="ds-heading-4">Commission ledger</h2>
                <p class="ds-body-sm mt-1 text-ink-500">Date, type, credit/debit, running balance, order, coupon, notes, status.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full ds-body-sm">
                    <thead class="bg-surface-muted text-left text-ink-500">
                        <tr>
                            <th class="px-5 py-3 font-medium">Date</th>
                            <th class="px-5 py-3 font-medium">Type</th>
                            <th class="px-5 py-3 font-medium text-right">Credit</th>
                            <th class="px-5 py-3 font-medium text-right">Debit</th>
                            <th class="px-5 py-3 font-medium text-right">Running Balance</th>
                            <th class="px-5 py-3 font-medium">Reference Order</th>
                            <th class="px-5 py-3 font-medium">Coupon</th>
                            <th class="px-5 py-3 font-medium">Admin Notes</th>
                            <th class="px-5 py-3 font-medium">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-ink-100">
                        @forelse ($ledger as $row)
                            <tr>
                                <td class="px-5 py-4 whitespace-nowrap text-ink-600">{{ $row->date?->format('M j, Y') }}</td>
                                <td class="px-5 py-4">{{ $row->type }}</td>
                                <td class="px-5 py-4 text-right font-medium text-emerald-700">
                                    @if ($row->credit > 0)+<x-money :amount="$row->credit" />@else —@endif
                                </td>
                                <td class="px-5 py-4 text-right font-medium text-red-600">
                                    @if ($row->debit > 0)−<x-money :amount="$row->debit" />@else —@endif
                                </td>
                                <td class="px-5 py-4 text-right font-semibold text-ink"><x-money :amount="$row->running_balance" /></td>
                                <td class="px-5 py-4 font-medium text-ink">{{ $row->order_number ?? '—' }}</td>
                                <td class="px-5 py-4 font-mono">{{ $row->coupon_code ?? '—' }}</td>
                                <td class="px-5 py-4 text-ink-600">{{ $row->admin_notes ?? '—' }}</td>
                                <td class="px-5 py-4">
                                    @if ($row->status === 'Paid' || $row->status === 'Completed')
                                        <span class="rounded-full bg-green-100 px-2 py-0.5 text-xs text-green-800">{{ $row->status }}</span>
                                    @elseif ($row->status === 'Pending')
                                        <span class="rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">{{ $row->status }}</span>
                                    @else
                                        <span class="rounded-full bg-gray-100 px-2 py-0.5 text-xs text-gray-700">{{ $row->status }}</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="px-5 py-8 text-center text-ink-500">No ledger transactions yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
