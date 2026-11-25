<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CatalogImage
{
    public const MIME_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function prepare(string $original, string $directory, string $field, bool $square = false): array
    {
        $disk = Storage::disk('local');
        $source = $disk->path($original);
        $info = is_file($source) ? @getimagesize($source) : false;
        if (! $info || ! in_array($info['mime'], self::MIME_TYPES, true)
            || max($info[0], $info[1]) > 4096 || filesize($source) > 12 * 1024 * 1024) {
            throw ValidationException::withMessages([$field => __('admin.catalog.invalid_image')]);
        }

        if ($square && $info[0] !== $info[1]) {
            throw ValidationException::withMessages([$field => __('admin.garments.square_image')]);
        }

        $image = @imagecreatefromstring(file_get_contents($source));
        if (! $image) {
            throw ValidationException::withMessages([$field => __('admin.artwork.unreadable')]);
        }

        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        $file = $directory.'/'.Str::uuid().'.webp';
        $disk->makeDirectory($directory);
        if (! imagewebp($image, $disk->path($file), 90) || ! $disk->exists($file) || $disk->size($file) === 0) {
            throw ValidationException::withMessages([$field => __('admin.artwork.write_failed')]);
        }

        return ['path' => $file, 'width' => $info[0]];
    }
}
