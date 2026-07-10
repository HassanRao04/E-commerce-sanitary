<?php

namespace App\Services;

use App\Models\OrderStatus;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class OrderWorkflowService
{
    private const CACHE_KEY = 'order.workflow.statuses';

    /** @return Collection<int, OrderStatus> */
    public function all(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addHour(), function (): Collection {
            return OrderStatus::query()->ordered()->get();
        });
    }

    /** @return Collection<int, OrderStatus> */
    public function active(): Collection
    {
        return $this->all()->where('is_active', true)->values();
    }

    public function find(?string $slug): ?OrderStatus
    {
        if (blank($slug)) {
            return null;
        }

        return $this->all()->firstWhere('slug', $slug);
    }

    public function findOrFail(string $slug): OrderStatus
    {
        return $this->find($slug) ?? throw new \InvalidArgumentException("Unknown order status [{$slug}].");
    }

    public function defaultStatus(): OrderStatus
    {
        return $this->all()->firstWhere('is_default', true)
            ?? $this->findOrFail('pending');
    }

    public function defaultSlug(): string
    {
        return $this->defaultStatus()->slug;
    }

    /** @return Collection<int, OrderStatus> */
    public function progressSteps(): Collection
    {
        return $this->active()
            ->where('show_in_progress', true)
            ->sortBy('sort_order')
            ->values();
    }

    public function label(?string $slug): string
    {
        return $this->find($slug)?->name
            ?? Str::headline(str_replace('_', ' ', (string) $slug));
    }

    public function badgeClasses(?string $slug): string
    {
        $color = $this->find($slug)?->badge_color ?? 'gray';

        return match ($color) {
            'amber' => 'bg-amber-100 text-amber-800',
            'blue' => 'bg-blue-100 text-blue-800',
            'indigo' => 'bg-indigo-100 text-indigo-800',
            'violet' => 'bg-violet-100 text-violet-800',
            'cyan' => 'bg-cyan-100 text-cyan-800',
            'sky' => 'bg-sky-100 text-sky-800',
            'emerald' => 'bg-emerald-100 text-emerald-800',
            'red' => 'bg-red-100 text-red-800',
            'orange' => 'bg-orange-100 text-orange-800',
            'rose' => 'bg-rose-100 text-rose-800',
            'teal' => 'bg-teal-100 text-teal-800',
            default => 'bg-gray-100 text-gray-700',
        };
    }

    public function isCancelled(?string $slug): bool
    {
        return (bool) $this->find($slug)?->is_cancellation;
    }

    public function isReturned(?string $slug): bool
    {
        return (bool) $this->find($slug)?->is_return;
    }

    public function isDelivered(?string $slug): bool
    {
        return (bool) $this->find($slug)?->is_delivered;
    }

    public function isTerminal(?string $slug): bool
    {
        return (bool) $this->find($slug)?->is_terminal;
    }

    /** @return list<string> */
    public function revenueExcludedSlugs(): array
    {
        return $this->all()
            ->filter(fn (OrderStatus $status): bool => $status->is_cancellation
                || $status->is_return
                || $status->customer_group === 'excluded')
            ->pluck('slug')
            ->all();
    }

    /** @return list<string> */
    public function slugsForCustomerGroup(string $group): array
    {
        return $this->active()
            ->where('customer_group', $group)
            ->pluck('slug')
            ->all();
    }

    /** @return array<string, int> */
    public function countsByStatus(): array
    {
        $counts = [];

        foreach ($this->active() as $status) {
            $counts[$status->slug] = (int) $status->orders()->count();
        }

        return $counts;
    }

    public function confirmedSlugForPayment(string $currentSlug): string
    {
        if ($currentSlug === $this->defaultSlug()) {
            return $this->find('confirmed')?->slug ?? 'confirmed';
        }

        return $currentSlug;
    }

    public function slugForShipmentDelivered(): string
    {
        return $this->find('delivered')?->slug ?? 'delivered';
    }

    public function slugForShipmentShipped(): string
    {
        return $this->find('shipped')?->slug ?? 'shipped';
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    /** @return list<string> */
    public function badgeColorOptions(): array
    {
        return ['gray', 'amber', 'blue', 'indigo', 'violet', 'cyan', 'sky', 'emerald', 'red', 'orange', 'rose', 'teal'];
    }
}
