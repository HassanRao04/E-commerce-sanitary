<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderWhatsAppJob;

class SendOrderWhatsAppNotification
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderWhatsAppJob::dispatch($event->order->id);
    }
}
