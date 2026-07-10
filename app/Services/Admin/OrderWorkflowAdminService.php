<?php

namespace App\Services\Admin;

use App\Models\OrderStatus;
use App\Services\OrderWorkflowService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderWorkflowAdminService
{
    public function __construct(
        private readonly OrderWorkflowService $workflow,
    ) {}

    /** @return Collection<int, OrderStatus> */
    public function list(): Collection
    {
        return OrderStatus::query()->ordered()->get();
    }

    /** @param  array<string, mixed>  $data */
    public function create(array $data): OrderStatus
    {
        return DB::transaction(function () use ($data): OrderStatus {
            $slug = $this->resolveSlug($data);

            if ($data['is_default'] ?? false) {
                OrderStatus::query()->update(['is_default' => false]);
            }

            $status = OrderStatus::create([
                'slug' => $slug,
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'badge_color' => $data['badge_color'] ?? 'gray',
                'sort_order' => (int) ($data['sort_order'] ?? 900),
                'is_system' => false,
                'is_default' => (bool) ($data['is_default'] ?? false),
                'is_cancellation' => (bool) ($data['is_cancellation'] ?? false),
                'is_return' => (bool) ($data['is_return'] ?? false),
                'is_delivered' => (bool) ($data['is_delivered'] ?? false),
                'is_terminal' => (bool) ($data['is_terminal'] ?? false),
                'show_in_progress' => (bool) ($data['show_in_progress'] ?? false),
                'customer_group' => $data['customer_group'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->workflow->clearCache();

            return $status;
        });
    }

    /** @param  array<string, mixed>  $data */
    public function update(OrderStatus $status, array $data): OrderStatus
    {
        return DB::transaction(function () use ($status, $data): OrderStatus {
            if (($data['is_default'] ?? false) && ! $status->is_default) {
                OrderStatus::query()->update(['is_default' => false]);
            }

            $payload = [
                'name' => trim($data['name']),
                'description' => $data['description'] ?? null,
                'badge_color' => $data['badge_color'] ?? $status->badge_color,
                'sort_order' => (int) ($data['sort_order'] ?? $status->sort_order),
                'is_default' => (bool) ($data['is_default'] ?? $status->is_default),
                'is_cancellation' => (bool) ($data['is_cancellation'] ?? false),
                'is_return' => (bool) ($data['is_return'] ?? false),
                'is_delivered' => (bool) ($data['is_delivered'] ?? false),
                'is_terminal' => (bool) ($data['is_terminal'] ?? false),
                'show_in_progress' => (bool) ($data['show_in_progress'] ?? false),
                'customer_group' => $data['customer_group'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ];

            if (! $status->is_system && filled($data['slug'] ?? null)) {
                $payload['slug'] = Str::slug($data['slug']);
            }

            $status->update($payload);
            $this->workflow->clearCache();

            return $status->fresh();
        });
    }

    public function delete(OrderStatus $status): void
    {
        if ($status->is_system) {
            throw ValidationException::withMessages([
                'status' => 'System statuses cannot be deleted.',
            ]);
        }

        if ($status->orders()->exists()) {
            throw ValidationException::withMessages([
                'status' => 'This status is assigned to existing orders and cannot be deleted.',
            ]);
        }

        if ($status->is_default) {
            throw ValidationException::withMessages([
                'status' => 'The default order status cannot be deleted.',
            ]);
        }

        $status->delete();
        $this->workflow->clearCache();
    }

    /** @param  array<string, mixed>  $data */
    private function resolveSlug(array $data): string
    {
        $slug = filled($data['slug'] ?? null)
            ? Str::slug($data['slug'])
            : Str::slug($data['name']);

        if ($slug === '') {
            throw ValidationException::withMessages(['slug' => 'A valid status slug is required.']);
        }

        return $slug;
    }
}
