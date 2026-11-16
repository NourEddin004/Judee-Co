<?php

namespace App\Models\Concerns;

use App\Domain\Media\ImageStore;
use Illuminate\Validation\ValidationException;

/**
 * The model's image file lives and dies with it: a replaced or deleted image is removed from
 * storage with its copies. A path can only point at an uploaded image, never elsewhere on disk.
 */
trait OwnsImageFile
{
    abstract protected function imageColumn(): string;

    public static function bootOwnsImageFile(): void
    {
        static::saving(function ($model) {
            $column = $model->imageColumn();
            $path = $model->getAttribute($column);

            if ($model->isDirty($column) && $path !== null && ! ImageStore::isSafePath($path)) {
                throw ValidationException::withMessages([$column => 'Upload the image again.']);
            }
        });

        static::updated(function ($model) {
            $column = $model->imageColumn();

            if ($model->wasChanged($column)) {
                app(ImageStore::class)->delete($model->getOriginal($column));
            }
        });

        static::deleted(fn ($model) => app(ImageStore::class)->delete($model->getAttribute($model->imageColumn())));
    }
}
