<?php

namespace App\Services\Admin;

use App\Enums\UserStatus;
use App\Models\Inquiry;
use App\Models\Notification;
use App\Models\User;

class InquiryNotificationService
{
    public function notifyStaff(Inquiry $inquiry): void
    {
        User::query()
            ->staff()
            ->where('status', UserStatus::Active)
            ->get()
            ->filter(fn (User $user): bool => $user->can('notifications.view'))
            ->each(function (User $user) use ($inquiry): void {
                Notification::query()->create([
                    'user_id' => $user->id,
                    'type' => 'admin.inquiry_received',
                    'title' => 'New Customer Inquiry Received',
                    'body' => $inquiry->name,
                    'data' => [
                        'inquiry_id' => $inquiry->id,
                        'customer_name' => $inquiry->name,
                    ],
                ]);
            });
    }
}
