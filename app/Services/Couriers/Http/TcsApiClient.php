<?php

namespace App\Services\Couriers\Http;

use App\Models\CourierProvider;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class TcsApiClient
{
    public function __construct(private readonly CourierProvider $provider) {}

    public function createBooking(array $payload): array
    {
        $payload['accesstoken'] = $this->accessToken();

        $response = $this->client()->post($this->path('booking_create'), $payload);

        return $this->parseBookingResponse($response);
    }

    /** @return array<string, mixed> */
    public function trackConsignment(string $consignmentNumber): array
    {
        $response = $this->client()
            ->withToken($this->accessToken())
            ->get($this->path('tracking'), [
                'consignee' => [$consignmentNumber],
            ]);

        $response->throw();

        return $response->json() ?? [];
    }

    public function downloadLabel(string $consignmentNumber): string
    {
        $response = $this->client()->get($this->path('print_label'), [
            'consignmentno' => $consignmentNumber,
            'shipperdetail' => 'true',
            'accesstoken' => $this->accessToken(),
        ]);

        if (! $response->successful()) {
            $message = $response->json('message') ?? 'Unable to download TCS label.';

            throw new RuntimeException($message);
        }

        $contentType = (string) $response->header('Content-Type');

        if (str_contains($contentType, 'pdf') || str_starts_with($response->body(), '%PDF')) {
            return $response->body();
        }

        $url = $response->json('url');

        if (filled($url)) {
            $pdfResponse = Http::timeout(30)->get($url);
            $pdfResponse->throw();

            return $pdfResponse->body();
        }

        throw new RuntimeException($response->json('message') ?? 'TCS label response did not contain a PDF.');
    }

    public function accessToken(): string
    {
        $cacheKey = 'courier.tcs.token.'.$this->provider->id;

        return Cache::remember($cacheKey, now()->addMinutes(50), function (): string {
            $response = $this->client()->get($this->path('authenticate'), [
                'username' => $this->provider->api_key,
                'password' => $this->provider->api_secret,
            ]);

            $response->throw();

            $token = $response->json('accesstoken')
                ?? $response->json('result.accessToken')
                ?? $response->json('accessToken');

            if (blank($token)) {
                throw new RuntimeException('TCS authentication did not return an access token.');
            }

            return (string) $token;
        });
    }

    /** @return array<string, mixed> */
    private function parseBookingResponse(Response $response): array
    {
        if (! $response->successful()) {
            $message = $response->json('message')
                ?? $response->json('error.0.errorname')
                ?? 'TCS booking request failed.';

            throw new RuntimeException($message);
        }

        $payload = $response->json() ?? [];
        $consignmentNo = data_get($payload, 'consignmentNo')
            ?? data_get($payload, 'consignmentno')
            ?? data_get($payload, 'response.consignmentNo');

        $status = data_get($payload, 'status');
        $message = data_get($payload, 'message', 'SUCCESS');

        if (blank($consignmentNo) || $status === false || strtoupper((string) $message) === 'FAILURE') {
            throw new RuntimeException((string) $message);
        }

        return [
            'consignmentNo' => (string) $consignmentNo,
            'message' => (string) $message,
            'raw' => $payload,
        ];
    }

    private function client(): PendingRequest
    {
        return Http::baseUrl(rtrim($this->baseUrl(), '/'))
            ->acceptJson()
            ->timeout(30);
    }

    private function baseUrl(): string
    {
        if (filled($this->provider->api_base_url)) {
            return (string) $this->provider->api_base_url;
        }

        return $this->provider->is_sandbox
            ? (string) config('couriers.providers_config.tcs.sandbox_base')
            : (string) config('couriers.providers_config.tcs.production_base');
    }

    private function path(string $key): string
    {
        return (string) config('couriers.providers_config.tcs.paths.'.$key);
    }
}
