<?php

namespace App\Services;

use App\Enums\CommissionLedgerStatus;
use App\Enums\CommissionLedgerType;
use App\Models\Cart;
use App\Models\Coupon;
use App\Models\InfluencerCommissionTransaction;
use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class CouponService
{
    public function __construct(
        private readonly CartService $cartService,
        private readonly CheckoutRulesEngine $rulesEngine,
        private readonly CheckoutRulesSettingsService $checkoutRulesSettings,
    ) {}

    public function apply(Cart $cart, string $code): Coupon
    {
        if (! $this->checkoutRulesSettings->couponsEnabled()) {
            throw ValidationException::withMessages([
                'code' => 'Coupons are not available at this time.',
            ]);
        }

        $coupon = Coupon::query()->valid()->byCode($code)->first();

        if (! $coupon) {
            throw ValidationException::withMessages([
                'code' => 'This coupon is invalid or has expired.',
            ]);
        }

        $cart->load('items');
        $subtotal = $this->rulesEngine->cartSubtotal($cart);
        $discount = $coupon->calculateDiscount($subtotal);

        if ($discount <= 0) {
            $message = $coupon->min_order_amount
                ? 'Minimum order amount of '.config('shop.currency_symbol').number_format((float) $coupon->min_order_amount, 2).' required.'
                : 'This coupon cannot be applied to your cart.';

            throw ValidationException::withMessages(['code' => $message]);
        }

        $cart->update(['coupon_id' => $coupon->id]);

        return $coupon->fresh();
    }

    public function remove(Cart $cart): void
    {
        $cart->update(['coupon_id' => null]);
    }

    /**
     * Persist influencer attribution on an order after it has been created.
     * Reuses order fields for customer, discount, amount, and date.
     */
    public function trackInfluencerOrder(Order $order, ?Coupon $coupon): void
    {
        if (! $coupon?->influencer_id) {
            return;
        }

        $order->forceFill([
            'coupon_id' => $coupon->id,
            'influencer_id' => $coupon->influencer_id,
            'influencer_commission_amount' => $coupon->calculateCommission((float) $order->grand_total),
        ])->save();

        $this->recordCommissionCredit($order->fresh());
    }

    /**
     * Ledger credit pointer for an attributed order (amount stays on the order row).
     */
    public function recordCommissionCredit(Order $order): InfluencerCommissionTransaction
    {
        if ($order->influencer_id === null) {
            throw ValidationException::withMessages([
                'order' => 'Order has no influencer attribution.',
            ]);
        }

        return InfluencerCommissionTransaction::query()->firstOrCreate(
            ['order_id' => $order->id],
            [
                'influencer_id' => $order->influencer_id,
                'type' => CommissionLedgerType::Credit,
                'amount' => null,
                'admin_notes' => null,
                'status' => CommissionLedgerStatus::Pending,
                'created_by' => null,
            ],
        );
    }

    /**
     * Manual payout debit against the influencer wallet.
     */
    public function recordCommissionDebit(
        User $influencer,
        float $amount,
        ?string $adminNotes = null,
        ?User $createdBy = null,
        ?Order $referenceOrder = null,
        ?string $transactionId = null,
    ): InfluencerCommissionTransaction {
        $amount = round($amount, 2);

        if ($amount <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Payout amount must be greater than zero.',
            ]);
        }

        $wallet = $this->influencerWallet($influencer);

        if ($amount > $wallet['balance'] + 0.00001) {
            throw ValidationException::withMessages([
                'amount' => 'Payout exceeds available wallet balance ('.number_format($wallet['balance'], 2).').',
            ]);
        }

        return InfluencerCommissionTransaction::query()->create([
            'influencer_id' => $influencer->id,
            'type' => CommissionLedgerType::Debit,
            'order_id' => null,
            'reference_order_id' => $referenceOrder?->id,
            'amount' => $amount,
            'admin_notes' => filled($adminNotes) ? $adminNotes : 'Manual payout',
            'transaction_id' => filled($transactionId) ? $transactionId : null,
            'status' => CommissionLedgerStatus::Completed,
            'created_by' => $createdBy?->id,
        ]);
    }

    /**
     * Pay a pending order commission: ledger debit + mark order paid.
     * Does not replace markCommissionPaid() — that remains available separately.
     */
    public function payOrderCommission(
        Order $order,
        float $amount,
        ?string $adminNotes = null,
        ?User $createdBy = null,
        ?string $transactionId = null,
    ): InfluencerCommissionTransaction {
        if ($order->influencer_id === null) {
            throw ValidationException::withMessages([
                'order' => 'Order has no influencer attribution.',
            ]);
        }

        if ($order->influencer_commission_paid_at !== null) {
            throw ValidationException::withMessages([
                'order' => 'This commission is already marked as paid.',
            ]);
        }

        $influencer = User::query()->findOrFail($order->influencer_id);
        $notes = filled($adminNotes)
            ? $adminNotes
            : 'Pay commission '.$order->order_number;

        $debit = $this->recordCommissionDebit(
            $influencer,
            $amount,
            $notes,
            $createdBy,
            $order,
            $transactionId,
        );

        $this->markCommissionPaid($order);

        return $debit;
    }

    /**
     * Mark an attributed order commission as paid (timestamp only).
     */
    public function markCommissionPaid(Order $order): Order
    {
        if ($order->influencer_id === null || $order->influencer_commission_paid_at !== null) {
            return $order;
        }

        $order->forceFill([
            'influencer_commission_paid_at' => now(),
        ])->save();

        return $order->fresh();
    }

    /**
     * Bulk-mark pending commissions as paid for an influencer (timestamp only).
     * Already-paid and non-matching orders are skipped.
     *
     * @param  list<int>  $orderIds
     * @return array{processed: int, skipped: int}
     */
    public function markCommissionsPaid(User $influencer, array $orderIds): array
    {
        $processed = 0;
        $skipped = 0;
        $ids = array_values(array_unique(array_map('intval', $orderIds)));

        if ($ids === []) {
            return ['processed' => 0, 'skipped' => 0];
        }

        $orders = Order::query()
            ->whereIn('id', $ids)
            ->where('influencer_id', $influencer->id)
            ->get();

        $foundIds = $orders->pluck('id')->all();
        $skipped += count(array_diff($ids, $foundIds));

        foreach ($orders as $order) {
            if ($order->influencer_commission_paid_at !== null) {
                $skipped++;

                continue;
            }

            $this->markCommissionPaid($order);
            $processed++;
        }

        return compact('processed', 'skipped');
    }

    /**
     * Aggregate influencer performance from stored order attribution fields.
     * Commission totals use influencer_commission_amount (no recalculation).
     *
     * @return Collection<int, object{
     *     influencer_id: int,
     *     coupon_id: int,
     *     influencer_name: string,
     *     coupon_code: string,
     *     total_orders: int,
     *     total_sales: float,
     *     total_discount: float,
     *     total_commission: float,
     *     pending_commission: float,
     *     paid_commission: float,
     *     last_order_at: \Illuminate\Support\Carbon|string|null
     * }>
     */
    public function influencerPerformance(): Collection
    {
        return Order::query()
            ->whereNotNull('influencer_id')
            ->whereNotNull('coupon_id')
            ->with(['influencer:id,name', 'trackedCoupon:id,code'])
            ->get([
                'id',
                'influencer_id',
                'coupon_id',
                'coupon_code',
                'grand_total',
                'discount_total',
                'influencer_commission_amount',
                'influencer_commission_paid_at',
                'created_at',
            ])
            ->groupBy(fn (Order $order): string => $order->influencer_id.'|'.$order->coupon_id)
            ->map(function (Collection $orders): object {
                $first = $orders->first();

                return (object) [
                    'influencer_id' => (int) $first->influencer_id,
                    'coupon_id' => (int) $first->coupon_id,
                    'influencer_name' => $first->influencer?->name ?? '—',
                    'coupon_code' => $first->trackedCoupon?->code ?? $first->coupon_code ?? '—',
                    'total_orders' => $orders->count(),
                    'total_sales' => round((float) $orders->sum('grand_total'), 2),
                    'total_discount' => round((float) $orders->sum('discount_total'), 2),
                    'total_commission' => round((float) $orders->sum('influencer_commission_amount'), 2),
                    'pending_commission' => round((float) $orders
                        ->whereNull('influencer_commission_paid_at')
                        ->sum('influencer_commission_amount'), 2),
                    'paid_commission' => round((float) $orders
                        ->whereNotNull('influencer_commission_paid_at')
                        ->sum('influencer_commission_amount'), 2),
                    'last_order_at' => $orders->max('created_at'),
                ];
            })
            ->sortByDesc(fn (object $row) => $row->last_order_at)
            ->values();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, coupon_id?: int|string|null, status?: string|null, search?: string|null}  $filters
     */
    public function influencerOrdersQuery(User $influencer, array $filters = []): Builder
    {
        $search = trim((string) ($filters['search'] ?? ''));

        return Order::query()
            ->where('influencer_id', $influencer->id)
            ->when(
                filled($filters['coupon_id'] ?? null),
                fn (Builder $query) => $query->where('coupon_id', (int) $filters['coupon_id']),
            )
            ->when(
                filled($filters['status'] ?? null),
                fn (Builder $query) => $query->where('status', $filters['status']),
            )
            ->when(
                filled($filters['from'] ?? null),
                fn (Builder $query) => $query->whereDate('created_at', '>=', $filters['from']),
            )
            ->when(
                filled($filters['to'] ?? null),
                fn (Builder $query) => $query->whereDate('created_at', '<=', $filters['to']),
            )
            ->when(
                $search !== '',
                fn (Builder $query) => $query->where(function (Builder $inner) use ($search): void {
                    $like = '%'.$search.'%';
                    $inner->where('order_number', 'like', $like)
                        ->orWhere('customer_name', 'like', $like)
                        ->orWhere('customer_email', 'like', $like)
                        ->orWhere('coupon_code', 'like', $like);
                }),
            );
    }

    /**
     * @return array{from: ?string, to: ?string, coupon_id: ?string, status: ?string, search: ?string}
     */
    public function influencerOrderFilters(Request $request): array
    {
        return [
            'from' => $request->filled('from') ? $request->string('from')->toString() : null,
            'to' => $request->filled('to') ? $request->string('to')->toString() : null,
            'coupon_id' => $request->filled('coupon_id') ? $request->string('coupon_id')->toString() : null,
            'status' => $request->filled('status') ? $request->string('status')->toString() : null,
            'search' => $request->filled('search') ? $request->string('search')->trim()->toString() : null,
        ];
    }

    /**
     * @param  array{from?: string|null, to?: string|null, coupon_id?: int|string|null, status?: string|null}  $filters
     * @return array{
     *     total_orders: int,
     *     total_sales: float,
     *     total_discount: float,
     *     total_commission: float,
     *     pending_commission: float,
     *     paid_commission: float,
     *     today_earnings: float,
     *     this_month_earnings: float,
     *     customers_count: int
     * }
     */
    public function influencerSummary(User $influencer, array $filters = []): array
    {
        $base = $this->influencerOrdersQuery($influencer, $filters);

        return [
            'total_orders' => (clone $base)->count(),
            'total_sales' => round((float) (clone $base)->sum('grand_total'), 2),
            'total_discount' => round((float) (clone $base)->sum('discount_total'), 2),
            'total_commission' => round((float) (clone $base)->sum('influencer_commission_amount'), 2),
            'pending_commission' => round((float) (clone $base)
                ->whereNull('influencer_commission_paid_at')
                ->sum('influencer_commission_amount'), 2),
            'paid_commission' => round((float) (clone $base)
                ->whereNotNull('influencer_commission_paid_at')
                ->sum('influencer_commission_amount'), 2),
            'today_earnings' => round((float) (clone $base)
                ->whereDate('created_at', today())
                ->sum('influencer_commission_amount'), 2),
            'this_month_earnings' => round((float) (clone $base)
                ->whereBetween('created_at', [
                    now()->copy()->startOfMonth(),
                    now()->copy()->endOfMonth(),
                ])
                ->sum('influencer_commission_amount'), 2),
            'customers_count' => (int) (clone $base)
                ->whereNotNull('user_id')
                ->distinct()
                ->count('user_id'),
        ];
    }

    /**
     * Payout history (debits only) for the logged-in influencer.
     * Does not include order/credit history.
     *
     * @return Collection<int, object{
     *     id: int,
     *     date: mixed,
     *     amount: float,
     *     reference: string|null,
     *     admin: string|null,
     *     status: string,
     *     payment_note: string|null,
     *     transaction_id: string|null
     * }>
     */
    public function influencerPayoutHistory(User $influencer, ?int $limit = null): Collection
    {
        $query = InfluencerCommissionTransaction::query()
            ->where('influencer_id', $influencer->id)
            ->where('type', CommissionLedgerType::Debit)
            ->with([
                'referenceOrder:id,order_number',
                'creator:id,name,first_name,last_name',
            ])
            ->latest('created_at')
            ->latest('id');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query->get()
            ->map(fn (InfluencerCommissionTransaction $tx): object => (object) [
                'id' => $tx->id,
                'date' => $tx->created_at,
                'amount' => round((float) ($tx->amount ?? 0), 2),
                'reference' => $tx->referenceOrder?->order_number,
                'admin' => $tx->creator?->name,
                'status' => $tx->displayStatus(),
                'payment_note' => $tx->admin_notes,
                'transaction_id' => $tx->transaction_id,
            ])
            ->values();
    }

    /**
     * Recent completed payouts for the influencer dashboard summary.
     *
     * @return Collection<int, object{
     *     id: int,
     *     date: mixed,
     *     amount: float,
     *     order_number: string|null,
     *     status: string
     * }>
     */
    public function influencerLatestPayouts(User $influencer, int $limit = 10): Collection
    {
        return $this->influencerPayoutHistory($influencer, $limit)
            ->map(fn (object $row): object => (object) [
                'id' => $row->id,
                'date' => $row->date,
                'amount' => $row->amount,
                'order_number' => $row->reference,
                'status' => $row->status,
            ])
            ->values();
    }

    /**
     * Influencer wallet: credits from order commissions minus completed ledger debits.
     * Credit amounts are never duplicated — always read from orders.
     *
     * @param  array{from?: string|null, to?: string|null, coupon_id?: int|string|null, status?: string|null, search?: string|null}  $filters
     * @return array{balance: float, pending: float, paid: float, credits_count: int, debits_total: float}
     */
    public function influencerWallet(User $influencer, array $filters = []): array
    {
        $summary = $this->influencerSummary($influencer, $filters);
        $debitsTotal = round((float) InfluencerCommissionTransaction::query()
            ->where('influencer_id', $influencer->id)
            ->where('type', CommissionLedgerType::Debit)
            ->where('status', CommissionLedgerStatus::Completed)
            ->sum('amount'), 2);

        return [
            'balance' => round($summary['total_commission'] - $debitsTotal, 2),
            'pending' => $summary['pending_commission'],
            'paid' => $summary['paid_commission'],
            'credits_count' => $summary['total_orders'],
            'debits_total' => $debitsTotal,
        ];
    }

    /**
     * Chronological commission ledger with running balance.
     * Credits pull amounts from related orders; debits use stored payout amounts.
     *
     * @return Collection<int, object{
     *     id: int,
     *     date: mixed,
     *     type: string,
     *     credit: float,
     *     debit: float,
     *     running_balance: float,
     *     order_number: string|null,
     *     coupon_code: string|null,
     *     admin_notes: string|null,
     *     status: string
     * }>
     */
    public function influencerLedger(User $influencer): Collection
    {
        $transactions = InfluencerCommissionTransaction::query()
            ->where('influencer_id', $influencer->id)
            ->with([
                'order:id,order_number,coupon_code,influencer_commission_amount,influencer_commission_paid_at',
                'order.trackedCoupon:id,code',
                'referenceOrder:id,order_number,coupon_code',
                'referenceOrder.trackedCoupon:id,code',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $running = 0.0;

        return $transactions->map(function (InfluencerCommissionTransaction $tx) use (&$running): object {
            $credit = $tx->creditAmount();
            $debit = $tx->debitAmount();
            $running = round($running + $credit - $debit, 2);
            $related = $tx->relatedOrder();

            return (object) [
                'id' => $tx->id,
                'date' => $tx->created_at,
                'type' => $tx->type->label(),
                'credit' => $credit,
                'debit' => $debit,
                'running_balance' => $running,
                'order_number' => $related?->order_number,
                'coupon_code' => $related?->trackedCoupon?->code ?? $related?->coupon_code,
                'admin_notes' => $tx->admin_notes,
                'status' => $tx->displayStatus(),
            ];
        })->values();
    }

    /**
     * @param  array{from?: string|null, to?: string|null, coupon_id?: int|string|null, status?: string|null}  $filters
     * @return Collection<int, object{
     *     user_id: int|null,
     *     customer_name: string,
     *     customer_email: string,
     *     orders_count: int,
     *     total_sales: float,
     *     total_commission: float,
     *     last_order_at: mixed
     * }>
     */
    public function influencerCustomers(User $influencer, array $filters = []): Collection
    {
        return $this->influencerOrdersQuery($influencer, $filters)
            ->get([
                'user_id',
                'customer_name',
                'customer_email',
                'grand_total',
                'influencer_commission_amount',
                'created_at',
            ])
            ->groupBy(fn (Order $order): string => (string) ($order->user_id ?: $order->customer_email))
            ->map(function (Collection $orders): object {
                $first = $orders->first();

                return (object) [
                    'user_id' => $first->user_id,
                    'customer_name' => $first->customer_name,
                    'customer_email' => $first->customer_email,
                    'orders_count' => $orders->count(),
                    'total_sales' => round((float) $orders->sum('grand_total'), 2),
                    'total_commission' => round((float) $orders->sum('influencer_commission_amount'), 2),
                    'last_order_at' => $orders->max('created_at'),
                ];
            })
            ->sortByDesc(fn (object $row) => $row->last_order_at)
            ->values();
    }

    /** @return list<string> */
    public function influencerExportHeaders(): array
    {
        return [
            'Order',
            'Customer',
            'Email',
            'Coupon',
            'Status',
            'Payment',
            'Sales',
            'Discount',
            'Commission',
            'Commission Status',
            'Date',
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<list<mixed>>
     */
    public function influencerExportRows(Collection $orders): array
    {
        return $orders->map(fn (Order $order): array => [
            $order->order_number,
            $order->customer_name,
            $order->customer_email,
            $order->trackedCoupon?->code ?? $order->coupon_code ?? '',
            $order->status,
            $order->payment_status?->value ?? '',
            (float) $order->grand_total,
            (float) $order->discount_total,
            (float) $order->influencer_commission_amount,
            $order->influencer_commission_paid_at ? 'Paid' : 'Pending',
            $order->created_at?->format('Y-m-d H:i') ?? '',
        ])->all();
    }

    /** @return list<string> */
    public function influencerCommissionExportHeaders(): array
    {
        return [
            'Order Number',
            'Coupon',
            'Commission',
            'Earned Date',
            'Paid Date',
            'Status',
        ];
    }

    /**
     * @param  Collection<int, Order>  $orders
     * @return list<list<mixed>>
     */
    public function influencerCommissionExportRows(Collection $orders): array
    {
        return $orders->map(fn (Order $order): array => [
            $order->order_number,
            $order->trackedCoupon?->code ?? $order->coupon_code ?? '',
            (float) $order->influencer_commission_amount,
            $order->created_at?->format('Y-m-d H:i') ?? '',
            $order->influencer_commission_paid_at?->format('Y-m-d H:i') ?? '',
            $order->influencer_commission_paid_at ? 'Paid' : 'Pending',
        ])->all();
    }
}
