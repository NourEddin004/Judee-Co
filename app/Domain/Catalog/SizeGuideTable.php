<?php

namespace App\Domain\Catalog;

/**
 * A size guide is edited as plain text: the first line holds the column titles, every
 * other line one size, cells separated by "|" (REQ-CMS-18). Stored as {"columns", "rows"}.
 */
final class SizeGuideTable
{
    public static function toText(?array $table): string
    {
        if (! $table || empty($table['columns'])) {
            return '';
        }

        return collect([$table['columns'], ...($table['rows'] ?? [])])
            ->map(fn (array $cells) => implode(' | ', $cells))
            ->implode("\n");
    }

    public static function fromText(?string $text): ?array
    {
        $lines = array_values(array_filter(array_map('trim', preg_split('/\R/u', (string) $text))));

        if ($lines === []) {
            return null;
        }

        $split = fn (string $line) => array_map('trim', explode('|', $line));

        return ['columns' => $split($lines[0]), 'rows' => array_map($split, array_slice($lines, 1))];
    }

    /** The problem with the text, or null when every line has as many cells as the title line. */
    public static function problem(?string $text): ?string
    {
        $table = self::fromText($text);

        if ($table === null) {
            return null;
        }

        if ($table['rows'] === []) {
            return 'Add at least one size line under the column titles.';
        }

        foreach ($table['rows'] as $i => $row) {
            if (count($row) !== count($table['columns'])) {
                return sprintf('Line %d has %d cells; the title line has %d.', $i + 2, count($row), count($table['columns']));
            }
        }

        return null;
    }
}
