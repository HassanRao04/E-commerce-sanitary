<?php

namespace App\Services\Admin;

use App\DataTransferObjects\DeletedRecordEntry;
use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\User;
use App\Services\ActivityLogService;
use App\Services\UserActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator as Paginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class DeletedRecordService
{
    public function __construct(
        private readonly ActivityLogService $activityLog,
        private readonly UserActivityLogService $userActivityLog,
        private readonly ProductService $products,
        private readonly CategoryService $categories,
        private readonly BrandService $brands,
    ) {}

    /**
     * @return array<string, string>
     */
    public function entityTypeOptions(): array
    {
        return collect(config('recoverable.entities'))
            ->mapWithKeys(fn (array $config, string $key): array => [$key => $config['label']])
            ->all();
    }

    /**
     * @return LengthAwarePaginator<int, DeletedRecordEntry>
     */
    public function search(Request $request, int $perPage = 25): LengthAwarePaginator
    {
        $typeFilter = $request->filled('type') ? $request->string('type')->toString() : null;
        $entities = $this->resolveEntities($typeFilter);

        $records = collect();

        foreach ($entities as $type => $config) {
            $query = $this->buildTrashedQuery($config);

            if ($request->filled('q')) {
                $term = $request->string('q')->toString();
                $this->applySearch($query, $config, $term);
            }

            if ($request->filled('deleted_from')) {
                $query->where('deleted_at', '>=', Carbon::parse($request->input('deleted_from'))->startOfDay());
            }

            if ($request->filled('deleted_to')) {
                $query->where('deleted_at', '<=', Carbon::parse($request->input('deleted_to'))->endOfDay());
            }

            foreach ($query->get() as $model) {
                $records->push($this->toEntry($type, $config, $model));
            }
        }

        if ($request->filled('deleted_by')) {
            $deletedById = (int) $request->input('deleted_by');
            $records = $records->filter(
                fn (DeletedRecordEntry $entry): bool => $entry->deletedBy?->id === $deletedById
            );
        }

        if ($request->filled('q')) {
            $term = mb_strtolower($request->string('q')->toString());
            $records = $records->filter(function (DeletedRecordEntry $entry) use ($term): bool {
                return str_contains(mb_strtolower($entry->identifier), $term)
                    || ($entry->subtitle !== null && str_contains(mb_strtolower($entry->subtitle), $term))
                    || str_contains((string) $entry->id, $term);
            });
        }

        $sorted = $records->sortByDesc(fn (DeletedRecordEntry $entry): int => $entry->deletedAt?->getTimestamp() ?? 0);

        return $this->paginateCollection($sorted->values(), $perPage, $request);
    }

    public function restore(string $type, int $id, User $actor): void
    {
        match ($type) {
            'user' => $this->restoreUser($id, $actor),
            'product' => $this->products->restore($this->findTrashed('product', $id)),
            'category' => $this->categories->restore($this->findTrashed('category', $id)),
            'brand' => $this->brands->restore($this->findTrashed('brand', $id)),
            'coupon' => $this->restoreCoupon($id, $actor),
            default => throw new InvalidArgumentException("Unknown recoverable entity type [{$type}]."),
        };
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function resolveEntities(?string $typeFilter): array
    {
        $entities = config('recoverable.entities', []);

        if ($typeFilter !== null) {
            if (! isset($entities[$typeFilter])) {
                throw new InvalidArgumentException("Unknown recoverable entity type [{$typeFilter}].");
            }

            return [$typeFilter => $entities[$typeFilter]];
        }

        return $entities;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function toEntry(string $type, array $config, Model $model): DeletedRecordEntry
    {
        $deletionLog = $this->resolveDeletionLog($config, $model);

        return new DeletedRecordEntry(
            type: $type,
            typeLabel: $config['label'],
            id: (int) $model->getKey(),
            identifier: $this->resolveIdentifier($model, $config),
            subtitle: $this->resolveSubtitle($model, $config),
            deletedAt: $model->deleted_at,
            deletedBy: $deletionLog?->user,
            status: 'Deleted',
        );
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveDeletionLog(array $config, Model $model): ?ActivityLog
    {
        $deletionLog = ActivityLog::query()
            ->with('user')
            ->where('model_type', $config['model'])
            ->where('model_id', $model->getKey())
            ->where('action', $config['delete_action'])
            ->latest('created_at')
            ->first();

        if ($deletionLog !== null || $config['model'] !== Category::class || ! $model instanceof Category) {
            return $deletionLog;
        }

        $ancestor = $model->parent()->withTrashed()->first();

        while ($ancestor !== null) {
            $ancestorLog = ActivityLog::query()
                ->with('user')
                ->where('model_type', Category::class)
                ->where('model_id', $ancestor->id)
                ->where('action', 'category.deleted')
                ->latest('created_at')
                ->first();

            if ($ancestorLog !== null) {
                return $ancestorLog;
            }

            $ancestor = $ancestor->parent()->withTrashed()->first();
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function buildTrashedQuery(array $config): Builder
    {
        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];

        $query = $modelClass::query()->onlyTrashed();

        if ($config['staff_only'] === true) {
            $query->staff();
        }

        return $query;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveIdentifier(Model $model, array $config): string
    {
        return (string) $model->getAttribute($config['identifier']);
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function resolveSubtitle(Model $model, array $config): ?string
    {
        if ($config['subtitle'] === null) {
            return null;
        }

        $value = $model->getAttribute($config['subtitle']);

        return $value !== null ? (string) $value : null;
    }

    /**
     * @param  array<string, mixed>  $config
     */
    private function applySearch(mixed $query, array $config, string $term): void
    {
        $model = $config['model'];

        match ($model) {
            User::class => $query->where(function ($builder) use ($term): void {
                $builder->where('email', 'like', "%{$term}%")
                    ->orWhere('first_name', 'like', "%{$term}%")
                    ->orWhere('last_name', 'like', "%{$term}%")
                    ->orWhere('name', 'like', "%{$term}%");
            }),
            Product::class => $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('base_sku', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            }),
            default => $query->where(function ($builder) use ($term): void {
                $builder->where('name', 'like', "%{$term}%")
                    ->orWhere('slug', 'like', "%{$term}%");
            }),
        };
    }

    private function findTrashed(string $type, int $id): Model
    {
        $config = config("recoverable.entities.{$type}");

        if ($config === null) {
            throw new InvalidArgumentException("Unknown recoverable entity type [{$type}].");
        }

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];

        return $modelClass::query()->onlyTrashed()->findOrFail($id);
    }

    private function restoreUser(int $id, User $actor): void
    {
        /** @var User $user */
        $user = $this->findTrashed('user', $id);

        $deletionLog = ActivityLog::query()
            ->where('model_type', User::class)
            ->where('model_id', $user->id)
            ->where('action', 'user.deleted')
            ->latest('created_at')
            ->first();

        $snapshot = $deletionLog?->old_values ?? [];

        $user->restore();

        if (filled($snapshot['role'] ?? null)) {
            $user->syncRoles([$snapshot['role']]);
        }

        $this->userActivityLog->logRestored($user, $actor, $snapshot);
    }

    private function restoreCoupon(int $id, User $actor): void
    {
        /** @var \App\Models\Coupon $coupon */
        $coupon = $this->findTrashed('coupon', $id);

        $deletionLog = ActivityLog::query()
            ->where('model_type', \App\Models\Coupon::class)
            ->where('model_id', $coupon->id)
            ->where('action', 'coupon.deleted')
            ->latest('created_at')
            ->first();

        $snapshot = $deletionLog?->old_values ?? $coupon->toArray();

        $coupon->restore();

        $this->activityLog->log(
            'coupon.restored',
            $coupon,
            $snapshot,
            $coupon->fresh()?->toArray(),
            sprintf('Restored coupon %s', $coupon->code),
            $actor->id,
        );
    }

    /**
     * @param  Collection<int, DeletedRecordEntry>  $items
     * @return LengthAwarePaginator<int, DeletedRecordEntry>
     */
    private function paginateCollection(Collection $items, int $perPage, Request $request): LengthAwarePaginator
    {
        $page = max(1, (int) $request->input('page', 1));
        $total = $items->count();
        $results = $items->slice(($page - 1) * $perPage, $perPage)->values();

        return new Paginator(
            $results,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );
    }
}
