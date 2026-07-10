<?php

namespace App\Services\Admin;

use App\Models\Category;
use App\Repositories\Contracts\CategoryRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CategoryService
{
    public function __construct(
        private readonly CategoryRepositoryInterface $categories,
        private readonly ActivityLogService $activityLog,
    ) {}

    public function paginatedList(?string $term = null, int $perPage = 20): LengthAwarePaginator
    {
        return $this->categories->paginatedList($term, $perPage);
    }

    public function tree(): Collection
    {
        return $this->categories->tree();
    }

    public function parentOptions(?Category $except = null): Collection
    {
        $query = Category::query()->defaultOrder();

        if ($except) {
            $query->where('id', '!=', $except->id);
        }

        return $query->get(['id', 'name', 'parent_id']);
    }

    public function create(array $data, ?UploadedFile $image = null, ?UploadedFile $bannerImage = null): Category
    {
        return DB::transaction(function () use ($data, $image, $bannerImage) {
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);
            $data['is_active'] = ! empty($data['is_active']);
            $data['sort_order'] = $data['sort_order'] ?? 0;

            $parentId = $data['parent_id'] ?? null;
            unset($data['parent_id'], $data['remove_image'], $data['remove_banner_image']);

            if ($parentId) {
                $parent = Category::findOrFail($parentId);
                $category = $parent->children()->create($data);
            } else {
                $category = Category::create($data);
            }

            $this->syncImages($category, $data, $image, $bannerImage);

            $this->activityLog->log('category.created', $category->fresh());

            return $category->fresh();
        });
    }

    public function update(Category $category, array $data, ?UploadedFile $image = null, ?UploadedFile $bannerImage = null): Category
    {
        return DB::transaction(function () use ($category, $data, $image, $bannerImage) {
            $old = $category->toArray();
            $data['slug'] = $data['slug'] ?? Str::slug($data['name']);

            $category->update(collect($data)->except(['parent_id', 'remove_image', 'remove_banner_image'])->toArray());

            if (array_key_exists('parent_id', $data) && $data['parent_id'] != $category->parent_id) {
                $this->moveNode($category, $data['parent_id']);
            }

            $this->syncImages($category, $data, $image, $bannerImage);

            $this->activityLog->log('category.updated', $category, $old, $category->fresh()->toArray());

            return $category->fresh();
        });
    }

    public function delete(Category $category): void
    {
        DB::transaction(function () use ($category) {
            $this->activityLog->log('category.deleted', $category, $category->toArray());
            $this->deleteStoredImages($category);
            $category->delete();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function syncImages(Category $category, array $data, ?UploadedFile $image, ?UploadedFile $bannerImage): void
    {
        $attributes = [];

        if ($image !== null) {
            if (filled($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $attributes['image'] = $image->store("categories/{$category->id}", 'public');
        } elseif (! empty($data['remove_image'])) {
            if (filled($category->image)) {
                Storage::disk('public')->delete($category->image);
            }

            $attributes['image'] = null;
        }

        if ($bannerImage !== null) {
            if (filled($category->banner_image)) {
                Storage::disk('public')->delete($category->banner_image);
            }

            $attributes['banner_image'] = $bannerImage->store("categories/{$category->id}", 'public');
        } elseif (! empty($data['remove_banner_image'])) {
            if (filled($category->banner_image)) {
                Storage::disk('public')->delete($category->banner_image);
            }

            $attributes['banner_image'] = null;
        }

        if ($attributes !== []) {
            $category->update($attributes);
        }
    }

    private function deleteStoredImages(Category $category): void
    {
        if (filled($category->image)) {
            Storage::disk('public')->delete($category->image);
        }

        if (filled($category->banner_image)) {
            Storage::disk('public')->delete($category->banner_image);
        }
    }

    private function moveNode(Category $category, ?int $parentId): void
    {
        if ($parentId) {
            $parent = Category::findOrFail($parentId);
            $category->appendToNode($parent)->save();
        } else {
            $category->saveAsRoot();
        }
    }
}
