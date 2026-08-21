<?php

namespace App\Services\Admin;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class ProductImageProcessor
{
    /**
     * Optimize an upload and store a single public-disk file.
     *
     * Photographic uploads are converted to WebP, resized to the configured
     * maximum dimension, and compressed. PNG inputs with meaningful alpha are
     * preserved as WebP with alpha rather than flattened JPEG-style output.
     */
    public function storeOptimized(UploadedFile $file, string $directory, string $filename): string
    {
        if (! extension_loaded('gd')) {
            throw new RuntimeException('The PHP GD extension is required to process product images.');
        }

        if (! function_exists('imagewebp')) {
            throw new RuntimeException('WebP support is required in the PHP GD extension for product images.');
        }

        $directory = trim($directory, '/');
        $filename = $this->normalizeFilename($filename);
        $path = "{$directory}/{$filename}";

        Storage::disk('public')->makeDirectory($directory);

        $source = $this->loadImage($file);
        if ($source === false) {
            throw new RuntimeException('The uploaded file could not be decoded as an image.');
        }

        $maxDimension = max(1, (int) config('media.product.max_dimension', 1600));
        $processed = $this->resizeToMaxDimension($source, $maxDimension);
        imagedestroy($source);

        $fullPath = Storage::disk('public')->path($path);
        $quality = max(1, min(100, (int) config('media.product.webp_quality', 82)));

        if (! imagewebp($processed, $fullPath, $quality)) {
            imagedestroy($processed);
            throw new RuntimeException('Failed to write optimized product image.');
        }

        imagedestroy($processed);

        return $path;
    }

    public function deletePublicFile(?string $path): void
    {
        if (blank($path) || str_starts_with((string) $path, 'http')) {
            return;
        }

        Storage::disk('public')->delete($path);
    }

    private function normalizeFilename(string $filename): string
    {
        $filename = trim(str_replace('\\', '/', $filename), '/');

        if (! str_ends_with(strtolower($filename), '.webp')) {
            $filename = pathinfo($filename, PATHINFO_FILENAME).'.webp';
        }

        return $filename;
    }

    /** @return \GdImage|false */
    private function loadImage(UploadedFile $file): \GdImage|false
    {
        $contents = file_get_contents($file->getRealPath() ?: $file->getPathname());
        if ($contents === false) {
            return false;
        }

        return @imagecreatefromstring($contents);
    }

    /** @param \GdImage $image */
    private function resizeToMaxDimension(\GdImage $image, int $maxDimension): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);

        if ($width <= $maxDimension && $height <= $maxDimension) {
            return $this->cloneImage($image);
        }

        if ($width >= $height) {
            $targetWidth = $maxDimension;
            $targetHeight = max(1, (int) round($height * ($maxDimension / $width)));
        } else {
            $targetHeight = $maxDimension;
            $targetWidth = max(1, (int) round($width * ($maxDimension / $height)));
        }

        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        $this->prepareCanvas($resized);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $width, $height);

        return $resized;
    }

    /** @param \GdImage $image */
    private function cloneImage(\GdImage $image): \GdImage
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $clone = imagecreatetruecolor($width, $height);
        $this->prepareCanvas($clone);
        imagealphablending($clone, true);
        imagecopy($clone, $image, 0, 0, 0, 0, $width, $height);
        imagesavealpha($clone, true);

        return $clone;
    }

    /** @param \GdImage $image */
    private function prepareCanvas(\GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
        imagealphablending($image, true);
    }
}
