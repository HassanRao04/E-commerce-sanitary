<?php

namespace App\Services\Admin;

use App\Models\CourierProvider;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class CourierProviderService
{
    public function __construct(private readonly ActivityLogService $activityLog) {}

    public function paginatedList(?string $term = null, int $perPage = 15): LengthAwarePaginator
    {
        return CourierProvider::query()
            ->withCount('shipments')
            ->search($term)
            ->ordered()
            ->paginate($perPage)
            ->withQueryString();
    }

    public function create(array $data, ?UploadedFile $logo = null): CourierProvider
    {
        return DB::transaction(function () use ($data, $logo) {
            $provider = CourierProvider::create($this->prepareAttributes($data));

            $this->syncLogo($provider, $data, $logo);
            $this->activityLog->log('courier_provider.created', $provider->fresh());

            return $provider->fresh();
        });
    }

    public function update(CourierProvider $provider, array $data, ?UploadedFile $logo = null): CourierProvider
    {
        return DB::transaction(function () use ($provider, $data, $logo) {
            $old = $provider->toArray();

            $provider->update($this->prepareAttributes($data, $provider));
            $this->syncLogo($provider, $data, $logo);

            $this->activityLog->log('courier_provider.updated', $provider->fresh(), $old, $provider->fresh()->toArray());

            return $provider->fresh();
        });
    }

    public function delete(CourierProvider $provider): void
    {
        if ($provider->slug === 'manual') {
            throw new RuntimeException('The manual courier provider cannot be deleted.');
        }

        if ($provider->shipments()->exists()) {
            throw new RuntimeException('This courier provider is linked to shipments and cannot be deleted.');
        }

        DB::transaction(function () use ($provider) {
            $this->activityLog->log('courier_provider.deleted', $provider, $provider->toArray());
            $this->deleteStoredLogo($provider);
            $provider->delete();
        });
    }

    /** @return array<string, mixed> */
    private function prepareAttributes(array $data, ?CourierProvider $existing = null): array
    {
        $attributes = [
            'name' => $data['name'],
            'slug' => $data['slug'] ?? Str::slug($data['name']),
            'is_active' => ! empty($data['is_active']),
            'is_sandbox' => ! empty($data['is_sandbox']),
            'tracking_url_template' => $data['tracking_url_template'] ?? null,
            'api_base_url' => $data['api_base_url'] ?? null,
            'account_number' => $data['account_number'] ?? null,
            'pickup_address' => $data['pickup_address'] ?? null,
            'pickup_city' => $data['pickup_city'] ?? null,
            'default_package_weight' => isset($data['default_package_weight']) && $data['default_package_weight'] !== ''
                ? $data['default_package_weight']
                : null,
            'sort_order' => $data['sort_order'] ?? ($existing?->sort_order ?? 0),
        ];

        $credentials = $existing?->credentials ?? [];

        if (filled($data['api_key'] ?? null)) {
            $credentials['api_key'] = $data['api_key'];
        }

        if (filled($data['api_secret'] ?? null)) {
            $credentials['api_secret'] = $data['api_secret'];
        }

        if ($credentials !== []) {
            $attributes['credentials'] = $credentials;
        }

        return $attributes;
    }

    /** @param  array<string, mixed>  $data */
    private function syncLogo(CourierProvider $provider, array $data, ?UploadedFile $logo): void
    {
        $attributes = [];

        if ($logo !== null) {
            $this->deleteStoredLogo($provider);
            $attributes['logo'] = $logo->store("courier-providers/{$provider->id}", 'public');
        } elseif (! empty($data['remove_logo'])) {
            $this->deleteStoredLogo($provider);
            $attributes['logo'] = null;
        }

        if ($attributes !== []) {
            $provider->update($attributes);
        }
    }

    private function deleteStoredLogo(CourierProvider $provider): void
    {
        if (filled($provider->logo)) {
            Storage::disk('public')->delete($provider->logo);
        }
    }
}
