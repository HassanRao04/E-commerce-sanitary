<?php

namespace App\Services\Admin;

use App\Enums\PaymentStatus;
use App\Models\Order;
use App\Models\OrderStatusHistory;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Services\ActivityLogService;
use App\Services\OrderWorkflowService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    public function __construct(
        private readonly OrderRepositoryInterface $orders,
        private readonly ActivityLogService $activityLog,
        private readonly OrderNotificationService $notifications,
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->orders->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function statusCounts(): array
    {
        return $this->workflow->countsByStatus();
    }

    public function findWithRelations(int $id): Order
    {
        return Order::query()
            ->with([
                'items.productVariant',
                'statusHistories.orderStatus',
                'orderStatus',
                'payments',
                'shipments.trackingEvents',
                'invoice.items',
                'billingAddress',
                'shippingAddress',
                'user',
                'influencer:id,name,email',
                'trackedCoupon:id,code',
            ])
            ->findOrFail($id);
    }

    public function updateStatus(Order $order, string $statusSlug, ?string $note = null): Order
    {
        $definition = $this->workflow->find($statusSlug);

        if (! $definition || ! $definition->is_active) {
            throw ValidationException::withMessages([
                'status' => 'The selected order status is not available.',
            ]);
        }

        return DB::transaction(function () use ($order, $statusSlug, $note, $definition) {
            $old = $order->status;
            $order->update(['status' => $statusSlug]);

            OrderStatusHistory::create([
                'order_id' => $order->id,
                'status' => $statusSlug,
                'note' => $note,
            ]);

            $this->notifications->notifyStatusChange($order, $old, $statusSlug, $note, $definition);

            $this->activityLog->log('order.status_updated', $order, [
                'status' => $old,
            ], ['status' => $statusSlug]);

            return $order->fresh(['orderStatus']);
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
        $cancelledSlug = $this->workflow->all()->firstWhere('is_cancellation', true)?->slug ?? 'cancelled';

        return $this->updateStatus($order, $cancelledSlug, $note ?? 'Order cancelled by admin');
    }
}
