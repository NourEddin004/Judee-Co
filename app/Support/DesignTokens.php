<?php

namespace App\Support;

use Filament\Support\Colors\Color;

/**
 * Reads colour values from resources/css/tokens.css, the one place they are defined (REQ-UX-01),
 * so the admin panel uses the storefront's own colours (REQ-UX-02).
 */
final class DesignTokens
{
    /** @var array<string, string>|null */
    private static ?array $colors = null;

    public static function color(string $name): string
    {
        self::$colors ??= self::read();

        return self::$colors[$name] ?? throw new \InvalidArgumentException("Unknown colour token --color-{$name}.");
    }

    /**
     * A Filament shade scale (50 to 950) built from the storefront's own colours, so the
     * admin's main colour is exactly JUDE's rose (REQ-UX-02).
     *
     * @return array<int, string>
     */
    public static function rosePalette(): array
    {
        $shades = [
            50 => 'blush-50', 100 => 'blush-100', 200 => 'blush-200', 300 => 'rose-soft', 400 => 'blush-300',
            500 => 'accent', 600 => 'rose', 700 => 'rose-strong', 800 => 'wine-deep', 900 => 'plum', 950 => 'ink',
        ];

        return array_map(fn (string $token) => Color::convertToOklch(self::color($token)), $shades);
    }

    /** @return array<string, string> */
    private static function read(): array
    {
        preg_match_all('/--color-([a-z0-9-]+):\s*(#[0-9a-fA-F]{3,8})\s*;/', file_get_contents(resource_path('css/tokens.css')), $matches);

        return array_combine($matches[1], $matches[2]);
    }
}
