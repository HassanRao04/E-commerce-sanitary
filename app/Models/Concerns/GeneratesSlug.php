<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait GeneratesSlug
{
    public static function bootGeneratesSlug(): void
    {
        static::creating(function (self $model): void {
            if (empty($model->slug) && filled($model->getSlugSource())) {
                $model->slug = static::makeUniqueSlug($model->getSlugSource());
            }
        });
    }

    protected function getSlugSource(): ?string
    {
        return $this->name ?? null;
    }

    protected static function makeUniqueSlug(string $source): string
    {
        $slug = Str::slug($source);
        $original = $slug;
        $count = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $original.'-'.$count;
            $count++;
        }

        return $slug;
    }
}
