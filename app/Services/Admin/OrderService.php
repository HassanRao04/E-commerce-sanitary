<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ActivityLogService $activityLog,
        private readonly OrderNotificationService $notifications,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->orders->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function statusCounts(): array
    {
        $counts = [];

        foreach (OrderStatus::cases() as $status) {
            $counts[$status->value] = Order::query()->where('status', $status)->count();
        }

        return $counts;
    }

    public function findWithRelations(int $id): Order
    {
        return Order::query()
            ->with([
                'items.productVariant',
                'statusHistories',
                'payments',
                'shipments.trackingEvents',
                'invoice.items',
                'billingAddress',
                'shippingAddress',
                'user',
            ])
            ->findOrFail($id);
    }

    public function updateStatus(Order $order, OrderStatus $status, ?string $note = null): Order
    {
        return DB::transaction(function () use ($order, $status, $note) {
            $old = $order->status;
            $order->update(['status' => $status]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $status,
                'note' => $note,
            ]);

            $this->notifications->notifyStatusChange($order, $old, $status, $note);

            $this->activityLog->log('order.status_updated', $order, [
                'status' => $old?->value,
            ], ['status' => $status->value]);

            return $order->fresh();
        });
    }

    public function updatePaymentStatus(Order $order, PaymentStatus $paymentStatus): Order
    {
        return DB::transaction(function () use ($order, $paymentStatus) {
            $old = $order->payment_status;
            $order->update(['payment_status' => $paymentStatus]);

            $this->activityLog->log('order.payment_status_updated', $order, [
                'payment_status' => $old?->value,
            ], ['payment_status' => $paymentStatus->value]);

            return $order->fresh();
        });
    }

    public function cancel(Order $order, ?string $note = null): Order
    {
        return $this->updateStatus($order, OrderStatus::Cancelled, $note ?? 'Order cancelled by admin');
    }
}
