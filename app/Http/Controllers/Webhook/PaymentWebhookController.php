<?php

namespace App\Http\Controllers\Webhook;

use App\Exceptions\PaymentWebhookException;
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
        } catch (InvalidArgumentException) {
            return response()->json(['message' => 'Unknown payment gateway.'], Response::HTTP_NOT_FOUND);
        }

        $eventType = $request->header('X-Webhook-Event')
            ?? $request->input('event_type')
            ?? $request->input('type')
            ?? $request->input('pp_ResponseMessage');

        try {
            $result = $this->webhooks->process(
                $method,
                $request->all(),
                is_string($eventType) ? $eventType : null,
                // Byte-exact body: signature verification must never run against
                // the parsed array, which loses key order and formatting.
                $request->getContent(),
                $request->headers->all(),
            );
        } catch (PaymentWebhookException $exception) {
            return response()->json(['message' => $exception->getMessage()], $exception->statusCode);
        }

        return response()->json([
            'status' => $result->status->value,
            'message' => $result->message,
            'reference' => $result->gatewayReference,
        ]);
    }
}
