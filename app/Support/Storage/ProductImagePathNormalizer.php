<?php

namespace App\Support\Storage;

final class ProductImagePathNormalizer
{
    public static function normalize(?string $raw): ?string
    {
        if ($raw === null) {
            return null;
        }

        $path = trim($raw);
        if ($path === '') {
            return null;
        }

        if (preg_match('#^https?://#i', $path)) {
            return null;
        }

        $path = (string) (parse_url($path, PHP_URL_PATH) ?? $path);
        $path = str_replace('\\', '/', $path);
        $path = ltrim($path, '/');

        $prefixes = [
            'storage/app/public/',
            'app/public/',
            'public/storage/',
            'storage/',
            'public/',
        ];

        foreach ($prefixes as $prefix) {
            if (str_starts_with($path, $prefix)) {
                $path = substr($path, strlen($prefix));
                break;
            }
        }

        if (str_starts_with($path, 'storage/products/')) {
            $path = substr($path, strlen('storage/'));
        }

        $path = ltrim($path, '/');

        if ($path === '' || str_contains($path, '..')) {
            return null;
        }

        return $path;
    }

    public static function isHistoricalOrphanPattern(string $normalizedPath): bool
    {
        return self::isHistoricalGalleryOrphan($normalizedPath)
            || self::isHistoricalVariantOrphan($normalizedPath);
    }

    public static function isHistoricalGalleryOrphan(string $normalizedPath): bool
    {
        return (bool) preg_match('#^products/\d+/gallery-\d+-[a-z0-9]{8}\.svg$#i', $normalizedPath);
    }

    public static function isHistoricalVariantOrphan(string $normalizedPath): bool
    {
        return (bool) preg_match('#^products/\d+/variants/[a-z0-9]{20,}\.svg$#i', $normalizedPath);
    }

    public static function isDeterministicPattern(string $normalizedPath): bool
    {
        return self::isDeterministicGallery($normalizedPath)
            || self::isDeterministicVariant($normalizedPath);
    }

    public static function isDeterministicGallery(string $normalizedPath): bool
    {
        return (bool) preg_match('#^products/\d+/gallery-\d+\.svg$#i', $normalizedPath);
    }

    public static function isDeterministicVariant(string $normalizedPath): bool
    {
        return (bool) preg_match('#^products/\d+/variants/\d+\.svg$#i', $normalizedPath);
    }

    public static function relativePublicDiskPath(string $absolutePath, string $publicRoot): ?string
    {
        $absolutePath = str_replace('\\', '/', $absolutePath);
        $publicRoot = rtrim(str_replace('\\', '/', $publicRoot), '/');

        if (! str_starts_with($absolutePath, $publicRoot.'/')) {
            return null;
        }

        return substr($absolutePath, strlen($publicRoot) + 1);
    }

    public static function isUnderProductsDirectory(string $normalizedPath): bool
    {
        return str_starts_with($normalizedPath, 'products/')
            && ! str_contains($normalizedPath, '..');
    }
}
