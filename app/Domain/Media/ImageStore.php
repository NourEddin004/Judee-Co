<?php

namespace App\Domain\Media;

use GdImage;
use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Stores uploaded images on the public disk. Every upload is decoded and re-encoded, so any
 * hidden code is dropped (REQ-SEC-31), saved as WebP under a new random name (REQ-SEC-32),
 * with resized WebP copies beside it (REQ-DB-15). Nothing on this disk can run as code
 * (REQ-SEC-20, deploy/nginx/jude.conf).
 */
class ImageStore
{
    /** Folders admins may upload into, and the only paths a form may point at. */
    public const FOLDERS = ['products', 'rack', 'reviews', 'brand'];

    private const SOURCES_CACHE = 'image-sources:';

    public function __construct(private readonly Filesystem $disk) {}

    public function decode(UploadedFile|string $file): GdImage
    {
        $contents = $file instanceof UploadedFile ? $file->get() : $file;
        $image = $contents ? @imagecreatefromstring($contents) : false;

        if (! $image instanceof GdImage) {
            throw InvalidImage::unreadable();
        }

        // Only the decoded pixels go on; the file's metadata and any other bytes are left behind
        // when the pixels are written out again as WebP.
        imagepalettetotruecolor($image);
        imagealphablending($image, false);
        imagesavealpha($image, true);

        return $image;
    }

    /** Product photos, review photos and the logo: largest side 1600 px plus 400 and 800 px copies. */
    public function store(UploadedFile|string $file, string $folder): string
    {
        $image = Gd::fit($this->decode($file), config('store.images.max_width'), config('store.images.max_width'));
        $variants = [];

        foreach (config('store.images.variant_widths') as $width) {
            if ($width < imagesx($image)) {
                $variants[$width] = Gd::resize($image, $width, (int) round(imagesy($image) * $width / imagesx($image)));
            }
        }

        return $this->put($image, $folder, $variants);
    }

    /**
     * Writes the image and its copies under one new random name.
     *
     * @param  array<int, GdImage>  $variants  keyed by the size in the copy's file name
     */
    public function put(GdImage $image, string $folder, array $variants = []): string
    {
        abort_unless(in_array($folder, self::FOLDERS, true), 500);

        $base = $folder.'/'.Str::random(40);
        $quality = config('store.images.quality');

        $this->disk->put("{$base}.webp", Gd::webp($image, $quality));

        foreach ($variants as $size => $variant) {
            $this->disk->put("{$base}-{$size}.webp", Gd::webp($variant, $quality));
        }

        return "{$base}.webp";
    }

    /**
     * Writes the resized copies next to an image that is already on the disk (the demo images
     * copied in by the seeders), so they are served like uploaded ones.
     */
    public function addCopies(string $path): void
    {
        $contents = $this->disk->get($path);
        [$fullWidth, $fullHeight] = getimagesizefromstring($contents) ?: throw InvalidImage::unreadable();
        $byHeight = str_starts_with($path, 'rack/');
        $image = null;

        foreach (config($byHeight ? 'store.rack.variant_heights' : 'store.images.variant_widths') as $size) {
            $copy = self::variantPath($path, $size);
            if ($size >= ($byHeight ? $fullHeight : $fullWidth) || $this->disk->exists($copy)) {
                continue;
            }

            $image ??= $this->decode($contents);
            [$width, $height] = $byHeight
                ? [(int) round($fullWidth * $size / $fullHeight), $size]
                : [$size, (int) round($fullHeight * $size / $fullWidth)];
            $this->disk->put($copy, Gd::webp(Gd::resize($image, max(1, $width), max(1, $height)), config('store.images.quality')));
        }

        Cache::forget(self::SOURCES_CACHE.$path);
    }

    /** Removes an image and its resized copies. */
    public function delete(?string $path): void
    {
        if (! self::isSafePath($path)) {
            return;
        }

        $base = Str::beforeLast($path, '.');
        $sizes = [...config('store.images.variant_widths'), ...config('store.rack.variant_heights')];

        $this->disk->delete([$path, ...array_map(fn ($size) => "{$base}-{$size}.webp", $sizes)]);
        Cache::forget(self::SOURCES_CACHE.$path);
    }

    /**
     * The image and its resized copies, keyed by pixel width, narrowest first.
     * Widths are read once and cached; stored file names never change.
     *
     * @return array<int, string>
     */
    public function sources(string $path): array
    {
        if (! self::isSafePath($path)) {
            return [];
        }

        return Cache::rememberForever(self::SOURCES_CACHE.$path, function () use ($path) {
            $sizes = [...config('store.images.variant_widths'), ...config('store.rack.variant_heights')];
            $sources = [];

            foreach ([...array_map(fn ($size) => self::variantPath($path, $size), $sizes), $path] as $file) {
                if ($this->disk->exists($file) && ($info = @getimagesizefromstring($this->disk->get($file)))) {
                    $sources[$info[0]] = $file;
                }
            }

            ksort($sources);

            return $sources;
        });
    }

    /** srcset value so the browser downloads the smallest copy that fills the space (REQ-DB-15, REQ-PRF-01). */
    public function srcset(?string $path): string
    {
        return collect($path ? $this->sources($path) : [])
            ->map(fn (string $file, int $width) => $this->disk->url($file)." {$width}w")
            ->implode(', ');
    }

    public static function variantPath(string $path, int $size): string
    {
        return Str::beforeLast($path, '.')."-{$size}.webp";
    }

    /** A stored image path: one of our folders, a plain file name, an image extension. */
    public static function isSafePath(?string $path): bool
    {
        return is_string($path)
            && preg_match('#^('.implode('|', self::FOLDERS).')/[A-Za-z0-9][A-Za-z0-9._-]*\.(webp|png|jpe?g)$#', $path) === 1
            && ! str_contains($path, '..');
    }
}
