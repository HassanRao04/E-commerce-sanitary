<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Inquiry;
use App\Models\Notification;
use Illuminate\Http\RedirectResponse;

class NotificationController extends Controller
{
    public function open(Notification $notification): RedirectResponse
    {
        abort_unless($notification->user_id === auth()->id(), 404);
        abort_unless($notification->type === 'admin.inquiry_received', 404);

        $notification->markAsRead();

        $inquiry = Inquiry::query()->find($notification->data['inquiry_id'] ?? null);

        if (! $inquiry) {
            return redirect()
                ->route('admin.dashboard')
                ->with('error', 'This inquiry is no longer available.');
        }

        $this->authorize('view', $inquiry);

        return redirect()->route('admin.inquiries.show', $inquiry);
    }
}
