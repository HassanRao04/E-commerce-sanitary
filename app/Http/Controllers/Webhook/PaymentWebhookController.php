<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Services\PaymentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use InvalidArgumentException;
use Symfony\Component\HttpFoundation\Response;

class PaymentWebhookController extends Controller
{
    public function __construct(private readonly PaymentWebhookService $webhooks) {}

    public function handle(Request $request, string $gateway): JsonResponse
    {
        try {
            $method = $this->webhooks->methodFromRoute($gateway);
        } catch (InvalidArgumentException $exception) {
            return response()->json(['message' => $exception->getMessage()], Response::HTTP_NOT_FOUND);
        }

        $eventType = $request->header('X-Webhook-Event')
            ?? $request->input('event_type')
            ?? $request->input('pp_ResponseMessage');

        $result = $this->webhooks->process(
            $method,
            $request->all(),
            is_string($eventType) ? $eventType : null,
        );

        return response()->json([
            'status' => $result->status->value,
            'message' => $result->message,
            'reference' => $result->gatewayReference,
        ]);
    }
}
