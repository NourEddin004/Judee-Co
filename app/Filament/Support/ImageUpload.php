<?php

namespace App\Filament\Support;

use App\Domain\Media\ImageStore;
use App\Domain\Media\InvalidImage;
use Filament\Forms\Components\FileUpload;
use Illuminate\Validation\ValidationException;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;

/**
 * Image upload field: JPG, PNG or WEBP of 5 MB or less (REQ-SEC-19), stored through the
 * image store so it is re-encoded under a random name with WebP copies (REQ-SEC-31, 32, DB-15).
 */
final class ImageUpload
{
    /** Wide enough for the admin's previews (180 px tall at most) on a sharp screen. */
    private const PREVIEW_WIDTH = 400;

    public static function make(string $name, string $folder): FileUpload
    {
        return self::base($name)
            ->saveUploadedFileUsing(fn (TemporaryUploadedFile $file, FileUpload $component) => self::guard(
                $component,
                fn () => app(ImageStore::class)->store($file, $folder),
            ))
            // The preview loads a small copy, so a product with many colours opens quickly (REQ-PRF-03).
            // The field still holds the full photo.
            ->getUploadedFileUsing(fn (FileUpload $component, string $file, string|array|null $storedFileNames) => self::smallPreview($component, $file, $storedFileNames));
    }

    /** Filament's details of a stored image, with the preview taken from its 400 px copy when it has one. */
    private static function smallPreview(FileUpload $component, string $file, string|array|null $storedFileNames): ?array
    {
        $details = $component->getUploadedFile($file, $storedFileNames);
        $copy = collect(app(ImageStore::class)->sources($file))->first(fn (string $path, int $width): bool => $width >= self::PREVIEW_WIDTH);

        return $details !== null && $copy !== null ? [...$details, 'url' => $component->getDisk()->url($copy)] : $details;
    }

    /** A file that is not really an image fails next to its field (SRS 4.1). */
    public static function guard(FileUpload $component, \Closure $store): mixed
    {
        try {
            return $store();
        } catch (InvalidImage $e) {
            throw ValidationException::withMessages([$component->getStatePath() => $e->getMessage()]);
        }
    }

    /** The same checks, with storing left to the caller (the hero rack fits images first). */
    public static function base(string $name): FileUpload
    {
        return ImageFileUpload::make($name)
            ->image()
            ->disk('public')
            ->visibility('public')
            ->acceptedFileTypes(config('store.images.mime_types'))
            ->maxSize(config('store.images.max_kb'))
            ->helperText(__('JPG, PNG or WEBP, up to 5 MB.'))
            ->deleteUploadedFileUsing(fn () => null);   // files go when the record changes (OwnsImageFile)
    }
}
