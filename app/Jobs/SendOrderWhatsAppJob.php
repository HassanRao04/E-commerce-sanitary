<?php

namespace App\Jobs;

use App\Exceptions\WhatsAppRetryableException;
use App\Services\Notifications\OrderWhatsAppNotificationService;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class SendOrderWhatsAppJob implements ShouldBeUnique, ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    /** @var array<int, int> */
    public array $backoff = [60, 300];

    public function __construct(public int $orderId) {}

    public function uniqueId(): string
    {
        return 'order-whatsapp-'.$this->orderId;
    }

    public function handle(OrderWhatsAppNotificationService $orderWhatsApp): void
    {
        Log::info('Processing order WhatsApp job.', ['order_id' => $this->orderId]);

        $sent = $orderWhatsApp->sendForOrder($this->orderId);

        if ($sent) {
            Log::info('Order WhatsApp job completed successfully.', ['order_id' => $this->orderId]);

            return;
        }

        Log::warning('Order WhatsApp job completed without delivery.', ['order_id' => $this->orderId]);
    }

    public function failed(?Throwable $exception): void
    {
        Log::error('Order WhatsApp job failed after retries.', [
            'order_id' => $this->orderId,
            'message' => $exception?->getMessage(),
            'retryable' => $exception instanceof WhatsAppRetryableException,
        ]);
    }
}
