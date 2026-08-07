<?php

namespace App\Jobs;

use App\Services\Notifications\OrderConfirmationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendOrderConfirmationJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(public int $orderId) {}

    public function uniqueId(): string
    {
        return 'order-confirmation-'.$this->orderId;
    }

    public function handle(OrderConfirmationService $orderConfirmation): void
    {
        $orderConfirmation->sendForOrder($this->orderId);
    }
}
