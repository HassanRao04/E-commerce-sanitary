<?php

namespace App\Services\Admin;

use App\Enums\OrderStatus;
use App\Models\Notification;
use App\Models\Order;
use App\Models\User;
use Illuminate\Support\Str;

class OrderNotificationService
{
    public function notifyStatusChange(Order $order, OrderStatus $previous, OrderStatus $current, ?string $note = null): void
    {
        if ($order->user_id) {
            $this->createUserNotification($order, $previous, $current, $note);
        }

        $this->notifyStaff($order, $previous, $current, $note);
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
        OrderStatus $previous,
        OrderStatus $current,
        ?string $note,
    ): void {
        Notification::create([
            'user_id' => $order->user_id,
            'type' => 'order.status_updated',
            'title' => 'Order '.$order->order_number.' is '.Str::headline($current->value),
            'body' => $note ?: 'Your order status changed from '.Str::headline($previous->value).' to '.Str::headline($current->value).'.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'previous_status' => $previous->value,
                'status' => $current->value,
            ],
        ]);
    }

    private function notifyStaff(Order $order, OrderStatus $previous, OrderStatus $current, ?string $note): void
    {
        $adminEmail = config('shop.admin_email');

        if (! $adminEmail) {
            return;
        }

        $admin = User::query()->where('email', $adminEmail)->first();

        if (! $admin || $admin->id === $order->user_id) {
            return;
        }

        if (in_array($current, [OrderStatus::Pending, OrderStatus::Confirmed], true) && $previous === $current) {
            return;
        }

        Notification::create([
            'user_id' => $admin->id,
            'type' => 'admin.order_status_updated',
            'title' => "Order {$order->order_number} → ".Str::headline($current->value),
            'body' => $note ?: "Status changed from ".Str::headline($previous->value).' to '.Str::headline($current->value).'.',
            'data' => [
                'order_id' => $order->id,
                'order_number' => $order->order_number,
                'status' => $current->value,
            ],
        ]);
    }
}
