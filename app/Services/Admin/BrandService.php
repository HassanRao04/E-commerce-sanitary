<?php

namespace App\Services\Admin;

use App\Models\ActivityLog;
use App\Models\Brand;
use App\Repositories\Contracts\BrandRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class BrandService
{
    public function __construct(
        private readonly BrandRepositoryInterface $brands,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function paginatedList(array $filters = [], int $perPage = 15): LengthAwarePaginator
    {
        return $this->brands->search($filters['q'] ?? null, $filters, $perPage);
    }

    public function create(array $data): Brand
    {
        return DB::transaction(function () use ($data) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $data['is_active'] = ! empty($data['is_active']);

            $brand = $this->brands->create($data);
            $this->activityLog->log('brand.created', $brand);

            return $brand;
        });
    }

    public function update(Brand $brand, array $data): Brand
    {
        return DB::transaction(function () use ($brand, $data) {
            $old = $brand->toArray();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            $brand = $this->brands->update($brand, $data);
            $this->activityLog->log('brand.updated', $brand, $old, $brand->toArray());

            return $brand;
        });
    }

    public function delete(Brand $brand): void
    {
        DB::transaction(function () use ($brand) {
            $this->activityLog->log('brand.deleted', $brand, $brand->toArray());
            $this->brands->delete($brand);
        });
    }

    public function restore(Brand $brand): Brand
    {
        return DB::transaction(function () use ($brand) {
            $snapshot = ActivityLog::query()
                ->where('model_type', Brand::class)
                ->where('model_id', $brand->id)
                ->where('action', 'brand.deleted')
                ->latest('created_at')
                ->value('old_values') ?? $brand->toArray();

            $brand->restore();

            $restored = $brand->fresh();
            $this->activityLog->log('brand.restored', $restored, $snapshot, $restored->toArray());

            return $restored;
        });
    }
}
