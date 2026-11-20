<?php

namespace App\Support;

use InvalidArgumentException;

/** The demo's icon set (REQ-UX-48), rendered inline and hidden from screen readers. */
class Icons
{
    /** Icons that point a direction and are mirrored on Arabic pages (REQ-UX-52). */
    public const DIRECTIONAL = ['arrow-right', 'chevron-left', 'chevron-right', 'truck'];

    private static ?array $icons = null;

    public static function all(): array
    {
        return self::$icons ??= json_decode(file_get_contents(resource_path('icons/icons.json')), true);
    }

    public static function svg(string $name, string $class = ''): string
    {
        $svg = self::all()[$name] ?? throw new InvalidArgumentException("Unknown icon '{$name}'.");
        $classes = trim('ds-icon '.(in_array($name, self::DIRECTIONAL, true) ? 'ds-icon--dir ' : '').$class);

        return str_replace('<svg ', '<svg class="'.$classes.'" aria-hidden="true" focusable="false" ', $svg);
    }
}
