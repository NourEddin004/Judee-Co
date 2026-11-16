<?php

namespace App\Filament\Support;

use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Grid;

/**
 * A customer-facing text in English and Arabic, side by side (REQ-CMS-38 to REQ-CMS-40).
 * Arabic may stay empty; the site then shows the English text.
 */
final class Bilingual
{
    /** @param  (\Closure(TextInput): TextInput)|null  $english  extra setup for the English field */
    public static function input(string $name, string $label, bool $required = false, int $max = 255, ?\Closure $english = null): Grid
    {
        return self::pair($name, $label, $required, function (string $field) use ($max, $english) {
            $input = TextInput::make($field)->maxLength($max);

            return $english && str_ends_with($field, '_en') ? $english($input) : $input;
        });
    }

    public static function textarea(string $name, string $label, bool $required = false, int $rows = 3, int $max = 5000): Grid
    {
        return self::pair($name, $label, $required, fn (string $field) => Textarea::make($field)->rows($rows)->maxLength($max));
    }

    private static function pair(string $name, string $label, bool $required, \Closure $make): Grid
    {
        return Grid::make(['default' => 1, 'md' => 2])
            ->schema([
                $make("{$name}_en")->label(__(':label (English)', ['label' => __($label)]))->required($required),
                $make("{$name}_ar")->label(__(':label (Arabic)', ['label' => __($label)]))->extraInputAttributes(['dir' => 'rtl', 'lang' => 'ar']),
            ])
            ->columnSpanFull();
    }
}
