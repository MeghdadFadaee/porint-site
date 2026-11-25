<?php

namespace App\Support;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class Artwork
{
    public function prepare(string $original): string
    {
        $disk = Storage::disk('local');
        $path = $disk->path($original);
        $info = is_file($path) ? @getimagesize($path) : false;

        if (! $info || $info[2] !== IMAGETYPE_PNG || max($info[0], $info[1]) > 4096 || filesize($path) > 12 * 1024 * 1024) {
            throw $this->invalid('requirements');
        }

        $source = @imagecreatefrompng($path);
        if (! $source) {
            throw $this->invalid('unreadable');
        }

        imagepalettetotruecolor($source);
        imagealphablending($source, false);
        imagesavealpha($source, true);
        $artwork = imagecropauto($source, IMG_CROP_TRANSPARENT);
        if (! $artwork) {
            throw $this->invalid('empty');
        }

        $scale = min(1, 1200 / max(imagesx($artwork), imagesy($artwork)));
        $resized = imagescale($artwork, max(1, (int) round(imagesx($artwork) * $scale)), max(1, (int) round(imagesy($artwork) * $scale)));
        if (! $resized) {
            throw $this->invalid('resize_failed');
        }

        imagealphablending($resized, false);
        imagesavealpha($resized, true);
        $file = 'artwork/'.Str::uuid().'.webp';
        $disk->makeDirectory('artwork');
        if (! imagewebp($resized, $disk->path($file), 91) || ! $disk->exists($file) || $disk->size($file) === 0) {
            throw $this->invalid('write_failed');
        }

        return $file;
    }

    private function invalid(string $reason): ValidationException
    {
        return ValidationException::withMessages(['data.original_path' => __('admin.artwork.'.$reason)]);
    }
}
