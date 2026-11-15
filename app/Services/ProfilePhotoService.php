<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

/**
 * Profile photo service (ported from SchoolOS):
 *
 * Every profile photo is stored as two WebP files on the `public` disk:
 * - `{name}.p512.webp` — stored in `users.photo_path`: profile cards & headers.
 * - `{name}.p96.webp`  — lists, navbars, dropdowns and chips (≤ 48 px on 2× screens).
 *
 * A multi-megabyte phone photo becomes ~15 KB and ~2 KB. EXIF orientation is
 * normalised before encoding, and every upload receives a fresh random filename
 * so browser/CDN caches never serve stale images.
 */
final class ProfilePhotoService
{
    public const SHARED_DIRECTORY = 'seed-avatars';

    public const LARGE = 512;

    public const SMALL = 96;

    private const LARGE_SUFFIX = '.p512.webp';

    private const SMALL_SUFFIX = '.p96.webp';

    /**
     * Resize an uploaded (or raw) image into both sizes under $directory and
     * return the large file's path, the one to store in `photo_path`.
     *
     * @throws ValidationException when the bytes are not an image GD can read
     */
    public function store(UploadedFile|string $source, string $directory = 'profile-photos', string $disk = 'public', ?string $name = null): string
    {
        if (! function_exists('imagewebp')) {
            throw new RuntimeException('PHP\'s GD extension has no WebP support, which profile photos need.');
        }

        $bytes = $source instanceof UploadedFile ? (string) file_get_contents($source->getRealPath()) : $source;
        $image = $this->read($bytes, $source instanceof UploadedFile ? $source->getRealPath() : null);

        $base = trim($directory, '/') . '/' . ($name ?? Str::random(40));

        Storage::disk($disk)->put($base . self::LARGE_SUFFIX, $this->encode($image, self::LARGE, 80));
        Storage::disk($disk)->put($base . self::SMALL_SUFFIX, $this->encode($image, self::SMALL, 78));

        return $base . self::LARGE_SUFFIX;
    }

    /**
     * Delete a stored photo and its small copy.
     */
    public function delete(?string $path, string $disk = 'public'): void
    {
        if (blank($path) || self::isShared($path)) {
            return;
        }

        Storage::disk($disk)->delete(array_unique([$path, self::smallPath($path)]));
    }

    public static function isShared(?string $path): bool
    {
        return $path !== null && str_starts_with($path, self::SHARED_DIRECTORY . '/');
    }

    public static function isOptimised(?string $path): bool
    {
        return $path !== null && str_ends_with($path, self::LARGE_SUFFIX);
    }

    public static function smallPath(string $path): string
    {
        return self::isOptimised($path)
            ? substr($path, 0, -strlen(self::LARGE_SUFFIX)) . self::SMALL_SUFFIX
            : $path;
    }

    public static function smallUrl(?string $url): ?string
    {
        if ($url === null || ! str_contains($url, self::LARGE_SUFFIX)) {
            return $url;
        }

        return str_replace(self::LARGE_SUFFIX, self::SMALL_SUFFIX, $url);
    }

    /**
     * @return \GdImage
     */
    private function read(string $bytes, ?string $file)
    {
        $image = $bytes === '' ? false : @imagecreatefromstring($bytes);

        if ($image === false) {
            throw ValidationException::withMessages(['photo' => __('common.photo_unreadable')]);
        }

        $orientation = 1;
        if (function_exists('exif_read_data')) {
            $exif = $file !== null
                ? @exif_read_data($file)
                : @exif_read_data('data://image/jpeg;base64,' . base64_encode($bytes));
            $orientation = (int) (is_array($exif) ? ($exif['Orientation'] ?? 1) : 1);
        }

        $rotated = match ($orientation) {
            3 => imagerotate($image, 180, 0),
            6 => imagerotate($image, -90, 0),
            8 => imagerotate($image, 90, 0),
            default => null,
        };

        if ($rotated !== null && $rotated !== false) {
            $image = $rotated;
        }

        return $image;
    }

    /**
     * @param  \GdImage  $image
     */
    private function encode($image, int $size, int $quality): string
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = min(1, $size / max(1, min($width, $height)));
        $newWidth = max(1, (int) round($width * $scale));
        $newHeight = max(1, (int) round($height * $scale));

        $canvas = imagecreatetruecolor($newWidth, $newHeight);
        imagealphablending($canvas, false);
        imagesavealpha($canvas, true);
        imagefill($canvas, 0, 0, imagecolorallocatealpha($canvas, 0, 0, 0, 127));
        imagecopyresampled($canvas, $image, 0, 0, 0, 0, $newWidth, $newHeight, $width, $height);

        ob_start();
        imagewebp($canvas, null, $quality);

        return (string) ob_get_clean();
    }
}
