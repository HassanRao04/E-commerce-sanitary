<?php

namespace App\Services\Admin;

use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderStatus;
use App\Models\User;
use App\Services\OrderWorkflowService;

class OrderNotificationService
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
    ) {}

    public function notifyStatusChange(
        Order $order,
        ?string $previous,
        string $current,
        ?string $note = null,
        ?OrderStatus $definition = null,
    ): void {
        if ($order->user_id) {
            $this->createUserNotification($order, $previous, $current, $note, $definition);
        }

        $this->notifyStaff($order, $previous, $current, $note, $definition);
    }

    public function notifyShipmentUpdate(Order $order, string $courier, ?string $trackingNumber): void
    {
        if (! $order->user_id) {
            return;
        }

        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order.shipment_updated',
            'title' => "Shipment update for {$order->order_number}",
            'body' => collect([
                $courier ? "Courier: {$courier}" : null,
                $trackingNumber ? "Tracking: {$trackingNumber}" : null,
            ])->filter()->implode(' · '),
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'tracking_number' => $trackingNumber,
            ],
        ]);
    }

    private function createUserNotification(
        Order $order,
        ?string $previous,
        string $current,
        ?string $note,
        ?OrderStatus $definition,
    ): void {
        $currentLabel = $definition?->name ?? $this->workflow->label($current);
        $previousLabel = $this->workflow->label($previous);

        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order.status_updated',
            'title' => 'Order '.$order->order_number.' is '.$currentLabel,
            'body' => $note ?: "Your order status changed from {$previousLabel} to {$currentLabel}.",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'previous_status' => $previous,
                'status' => $current,
            ],
        ]);
    }

    private function notifyStaff(
        Order $order,
        ?string $previous,
        string $current,
        ?string $note,
        ?OrderStatus $definition,
    ): void {
        $adminEmail = config('shop.admin_email');

        if (! $adminEmail) {
            return;
        }

        $admin = User::query()->where('email', $adminEmail)->first();

        if (! $admin || $admin->id === $order->user_id) {
            return;
        }

        $pendingSlugs = $this->workflow->slugsForCustomerGroup('pending');

        if (in_array($current, $pendingSlugs, true) && $previous === $current) {
            return;
        }

        $currentLabel = $definition?->name ?? $this->workflow->label($current);
        $previousLabel = $this->workflow->label($previous);

        Notification::create([
            'user_id' => $admin->id,
            'type' => 'admin.order_status_updated',
            'title' => "Order {$order->order_number} → {$currentLabel}",
            'body' => $note ?: "Status changed from {$previousLabel} to {$currentLabel}.",
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $current,
            ],
        ]);
    }
}
