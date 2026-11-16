<?php

namespace App\Models\Concerns;

/**
 * Bilingual columns stored as {field}_en and {field}_ar (REQ-DB-09).
 * t('name') returns the current locale's value, falling back to English while
 * the Arabic text is still waiting for the owner (REQ-DMO-87).
 */
trait HasTranslations
{
    public function t(string $field, ?string $locale = null): ?string
    {
        $locale ??= app()->getLocale();
        $value = $this->getAttribute("{$field}_{$locale}");

        if ($value === null || $value === '') {
            $value = $this->getAttribute("{$field}_en");
        }

        return $value;
    }
}
