<?php

namespace App\Services\Notifications;

use App\Exceptions\WhatsAppRetryableException;
use App\Support\SocialLinks;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WhatsAppNotificationService
{
    /** @var list<string> */
    private const PLACEHOLDER_VALUES = [
        'your_meta_access_token',
        'your_phone_number_id',
        'your_whatsapp_token',
        'your_whatsapp_phone_number_id',
    ];

    public function isConfigured(): bool
    {
        return $this->configurationIssues() === [];
    }

    public function usesTemplateMode(): bool
    {
        return strtolower((string) config('services.whatsapp.message_mode', 'template')) === 'template';
    }

    /** @return list<string> */
    public function configurationIssues(): array
    {
        $issues = [];
        $token = trim((string) config('services.whatsapp.token'));
        $phoneNumberId = trim((string) config('services.whatsapp.phone_number_id'));

        if ($token === '') {
            $issues[] = 'WHATSAPP_TOKEN is missing';
        } elseif ($this->isPlaceholder($token)) {
            $issues[] = 'WHATSAPP_TOKEN is still a placeholder value';
        }

        if ($phoneNumberId === '') {
            $issues[] = 'WHATSAPP_PHONE_NUMBER_ID is missing';
        } elseif ($this->isPlaceholder($phoneNumberId)) {
            $issues[] = 'WHATSAPP_PHONE_NUMBER_ID is still a placeholder value';
        } elseif (! ctype_digit($phoneNumberId)) {
            $issues[] = 'WHATSAPP_PHONE_NUMBER_ID must be numeric';
        }

        if ($this->usesTemplateMode() && blank(config('services.whatsapp.order_template'))) {
            $issues[] = 'WHATSAPP_ORDER_TEMPLATE is missing';
        }

        return $issues;
    }

    /**
     * @param  list<string>  $templateBodyParameters
     */
    public function sendOrderConfirmation(
        string $phone,
        ?int $orderId = null,
        array $templateBodyParameters = [],
        ?string $fallbackText = null,
    ): bool {
        if ($this->usesTemplateMode()) {
            return $this->sendTemplate(
                phone: $phone,
                templateName: (string) config('services.whatsapp.order_template', 'hello_world'),
                languageCode: (string) config('services.whatsapp.order_template_language', 'en_US'),
                bodyParameters: $templateBodyParameters,
                orderId: $orderId,
            );
        }

        if (blank($fallbackText)) {
            Log::warning('WhatsApp text mode requires message body.', [
                'order_id' => $orderId,
            ]);

            return false;
        }

        return $this->sendText($phone, $fallbackText, $orderId);
    }

    /**
     * @param  list<string>  $bodyParameters
     */
    public function sendTemplate(
        string $phone,
        string $templateName,
        string $languageCode,
        array $bodyParameters = [],
        ?int $orderId = null,
    ): bool {
        $recipient = $this->resolveRecipient($phone, $orderId);

        if ($recipient === null) {
            return false;
        }

        if (! $this->isConfigured()) {
            Log::warning('WhatsApp template send skipped: invalid API configuration.', [
                'order_id' => $orderId,
                'issues' => $this->configurationIssues(),
            ]);

            return false;
        }

        $template = [
            'name' => $templateName,
            'language' => ['code' => $languageCode],
        ];

        if ($bodyParameters !== []) {
            $template['components'] = [
                [
                    'type' => 'body',
                    'parameters' => array_map(
                        fn (string $text): array => ['type' => 'text', 'text' => $text],
                        $bodyParameters,
                    ),
                ],
            ];
        }

        return $this->dispatchPayload($recipient, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'template',
            'template' => $template,
        ], $orderId, 'template');
    }

    public function sendText(string $phone, string $message, ?int $orderId = null): bool
    {
        $recipient = $this->resolveRecipient($phone, $orderId);

        if ($recipient === null) {
            return false;
        }

        if (! $this->isConfigured()) {
            Log::warning('WhatsApp text send skipped: invalid API configuration.', [
                'order_id' => $orderId,
                'issues' => $this->configurationIssues(),
            ]);

            return false;
        }

        return $this->dispatchPayload($recipient, [
            'messaging_product' => 'whatsapp',
            'recipient_type' => 'individual',
            'to' => $recipient,
            'type' => 'text',
            'text' => [
                'preview_url' => true,
                'body' => $message,
            ],
        ], $orderId, 'text');
    }

    /** @param  array<string, mixed>  $payload */
    private function dispatchPayload(
        string $recipient,
        array $payload,
        ?int $orderId,
        string $messageType,
    ): bool {
        $url = $this->apiUrl();

        try {
            Log::info('WhatsApp API request dispatching.', [
                'order_id' => $orderId,
                'phone' => $this->maskPhone($recipient),
                'message_type' => $messageType,
                'endpoint' => $url,
                'template_name' => $messageType === 'template'
                    ? ($payload['template']['name'] ?? null)
                    : null,
            ]);

            $response = Http::withToken((string) config('services.whatsapp.token'))
                ->acceptJson()
                ->timeout(30)
                ->post($url, $payload);

            if ($response->failed()) {
                return $this->handleFailedResponse($response, $recipient, $orderId, $messageType, $url);
            }

            Log::info('WhatsApp message accepted by Meta.', [
                'order_id' => $orderId,
                'phone' => $this->maskPhone($recipient),
                'message_type' => $messageType,
                'message_id' => $response->json('messages.0.id'),
                'endpoint' => $url,
            ]);

            return true;
        } catch (WhatsAppRetryableException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            Log::error('WhatsApp transport error.', [
                'order_id' => $orderId,
                'phone' => $this->maskPhone($recipient),
                'message_type' => $messageType,
                'endpoint' => $url,
                'error_message' => $exception->getMessage(),
            ]);

            if ($this->isRetryableTransportError($exception)) {
                throw new WhatsAppRetryableException($exception->getMessage());
            }

            return false;
        }
    }

    private function resolveRecipient(string $phone, ?int $orderId): ?string
    {
        $recipient = SocialLinks::normalizePhoneForWhatsapp($phone);

        if ($recipient === null) {
            Log::warning('WhatsApp send skipped: invalid recipient phone.', [
                'order_id' => $orderId,
                'phone' => $this->maskPhone($phone),
            ]);
        }

        return $recipient;
    }

    private function apiUrl(): string
    {
        return sprintf(
            'https://graph.facebook.com/%s/%s/messages',
            (string) config('services.whatsapp.api_version', 'v21.0'),
            (string) config('services.whatsapp.phone_number_id'),
        );
    }

    private function handleFailedResponse(
        Response $response,
        string $recipient,
        ?int $orderId,
        string $messageType,
        string $endpoint,
    ): bool {
        $status = $response->status();
        $errorMessage = (string) ($response->json('error.message') ?? $response->body());
        $errorCode = $response->json('error.code');
        $errorSubcode = $response->json('error.error_subcode');

        Log::error('WhatsApp API request failed.', [
            'order_id' => $orderId,
            'phone' => $this->maskPhone($recipient),
            'message_type' => $messageType,
            'status' => $status,
            'error_code' => $errorCode,
            'error_subcode' => $errorSubcode,
            'error_message' => $errorMessage,
            'endpoint' => $endpoint,
            'response_body' => $response->json() ?? $response->body(),
        ]);

        if ($this->shouldRetryHttpFailure($status, $errorMessage)) {
            throw new WhatsAppRetryableException($errorMessage, $status);
        }

        return false;
    }

    private function shouldRetryHttpFailure(int $status, string $errorMessage): bool
    {
        if ($status >= 500 || $status === 429) {
            return true;
        }

        $normalized = strtolower($errorMessage);

        if (str_contains($normalized, 'oauth')
            || str_contains($normalized, 'access token')
            || str_contains($normalized, 'permission')
            || str_contains($normalized, 'invalid parameter')
            || str_contains($normalized, 'template')
            || str_contains($normalized, 're-engagement')
            || str_contains($normalized, '24 hour')) {
            return false;
        }

        return false;
    }

    private function isRetryableTransportError(\Throwable $exception): bool
    {
        $message = strtolower($exception->getMessage());

        return str_contains($message, 'timed out')
            || str_contains($message, 'connection')
            || str_contains($message, 'curl error 28');
    }

    private function isPlaceholder(string $value): bool
    {
        $normalized = strtolower(trim($value));

        if (in_array($normalized, self::PLACEHOLDER_VALUES, true)) {
            return true;
        }

        return str_starts_with($normalized, 'your_')
            || str_starts_with($normalized, 'replace_')
            || str_contains($normalized, 'placeholder');
    }

    private function maskPhone(?string $phone): ?string
    {
        if (blank($phone)) {
            return null;
        }

        $digits = preg_replace('/\D+/', '', $phone) ?? '';

        if (strlen($digits) <= 4) {
            return $digits;
        }

        return substr($digits, 0, 3).str_repeat('*', max(strlen($digits) - 7, 0)).substr($digits, -4);
    }
}
