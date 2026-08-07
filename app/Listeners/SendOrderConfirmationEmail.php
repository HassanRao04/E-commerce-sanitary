<?php

namespace App\Listeners;

use App\Events\OrderPlaced;
use App\Jobs\SendOrderConfirmationJob;

class SendOrderConfirmationEmail
{
    public function handle(OrderPlaced $event): void
    {
        SendOrderConfirmationJob::dispatch($event->order->id);
    }
}
