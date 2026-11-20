<?php

namespace App\Filament\Support;

use Filament\Forms\Components\FileUpload;
use Filament\Support\Components\Attributes\ExposedLivewireMethod;
use Livewire\Attributes\Renderless;
use Livewire\Features\SupportFileUploads\TemporaryUploadedFile;
use Throwable;

/**
 * Filament's upload field, keeping a photo that was picked but not saved yet on screen.
 *
 * Filament gives the browser no address for a photo that is still only uploaded (not saved), so
 * whenever the form redraws (adding a color, adding a photo, switching "Main photo"), the field
 * redraws without it and the photo looks removed. Here such a photo gets its temporary preview
 * address, so it stays in its box until the product is saved.
 */
class ImageFileUpload extends FileUpload
{
    #[ExposedLivewireMethod]
    #[Renderless]
    public function getUploadedFiles(): ?array
    {
        $files = parent::getUploadedFiles() ?? [];

        foreach ($this->getRawState() ?? [] as $key => $file) {
            if (! $file instanceof TemporaryUploadedFile || ($files[$key] ?? null) !== null) {
                continue;
            }

            try {
                $files[$key] = [
                    'name' => $file->getClientOriginalName(),
                    'size' => $file->getSize(),
                    'type' => $file->getMimeType(),
                    'url' => $file->temporaryUrl(),
                ];
            } catch (Throwable) {
                // Not previewable or already gone: leave it to Filament as before.
            }
        }

        return $files;
    }
}
