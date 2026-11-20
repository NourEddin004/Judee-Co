<?php

namespace App\Domain\Catalog;

/**
 * The height and weight each size fits, used by "Find my size" on the product page. Stored on the
 * size guide as a list of {size, height_min, height_max, weight_min, weight_max} (cm and kg).
 *
 * The defaults are general starting values for loose modest wear, not JUDE's own chart: the owner
 * replaces them under Size guides.
 */
final class SizeFit
{
    /** @var array<string, array{0:int,1:int,2:int,3:int}> size => [height min, height max, weight min, weight max] */
    public const DEFAULTS = [
        'XS' => [145, 157, 38, 47],
        'S' => [150, 160, 45, 55],
        'M' => [155, 165, 55, 65],
        'L' => [158, 170, 65, 75],
        'XL' => [160, 175, 75, 85],
        'XXL' => [162, 180, 85, 98],
        '3XL' => [165, 182, 98, 112],
    ];

    /** Default rows for the sizes that have one, in the given order. */
    public static function defaultsFor(array $sizes): array
    {
        $rows = [];

        foreach ($sizes as $size) {
            $key = strtoupper(trim((string) $size));
            if (isset(self::DEFAULTS[$key])) {
                [$hMin, $hMax, $wMin, $wMax] = self::DEFAULTS[$key];
                $rows[] = ['size' => trim((string) $size), 'height_min' => $hMin, 'height_max' => $hMax, 'weight_min' => $wMin, 'weight_max' => $wMax];
            }
        }

        return $rows;
    }

    /** Clean rows: every number present and each minimum not above its maximum. */
    public static function clean(?array $rows): array
    {
        return array_values(array_filter(array_map(function ($row) {
            $size = trim((string) ($row['size'] ?? ''));
            $numbers = array_map(fn ($key) => is_numeric($row[$key] ?? null) ? (int) $row[$key] : null, ['height_min', 'height_max', 'weight_min', 'weight_max']);

            if ($size === '' || in_array(null, $numbers, true) || $numbers[0] > $numbers[1] || $numbers[2] > $numbers[3]) {
                return null;
            }

            return ['size' => $size, 'height_min' => $numbers[0], 'height_max' => $numbers[1], 'weight_min' => $numbers[2], 'weight_max' => $numbers[3]];
        }, $rows ?? [])));
    }

    /**
     * The size for a height (cm) and weight (kg): the row they are closest to, weight counting
     * twice as much as height, since it decides how a loose cut sits. Between two sizes the larger
     * one wins. Mirrors resources/js/store/fit.js, which does the same in the browser.
     *
     * @return array{size: string, exact: bool}|null
     */
    public static function recommend(array $rows, float $height, float $weight): ?array
    {
        $best = null;

        foreach (self::clean($rows) as $index => $row) {
            $distance = 2 * self::outside($weight, $row['weight_min'], $row['weight_max'])
                + self::outside($height, $row['height_min'], $row['height_max']);

            if ($best === null || $distance <= $best['distance']) {
                $best = ['size' => $row['size'], 'distance' => $distance, 'index' => $index];
            }
        }

        return $best === null ? null : ['size' => $best['size'], 'exact' => $best['distance'] == 0];
    }

    private static function outside(float $value, int $min, int $max): float
    {
        return $value < $min ? $min - $value : ($value > $max ? $value - $max : 0.0);
    }
}
