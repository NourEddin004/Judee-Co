<?php

namespace App\Filament\Support;

use Illuminate\Support\Str;

/** A resource's names in the admin's language: English, or Arabic from lang/ar.json. */
trait TranslatedLabels
{
    private static function englishModelLabel(): string
    {
        return static::$modelLabel ?? Str::lower(Str::headline(class_basename(static::getModel())));
    }

    private static function englishPluralModelLabel(): string
    {
        return static::$pluralModelLabel ?? Str::plural(static::englishModelLabel());
    }

    public static function getModelLabel(): string
    {
        return __(static::englishModelLabel());
    }

    public static function getPluralModelLabel(): string
    {
        return __(static::englishPluralModelLabel());
    }

    public static function getNavigationLabel(): string
    {
        return __(static::$navigationLabel ?? Str::ucfirst(static::englishPluralModelLabel()));
    }
}
