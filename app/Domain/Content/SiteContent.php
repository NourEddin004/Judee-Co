<?php

namespace App\Domain\Content;

use App\Models\ContentBlock;
use App\Models\Faq;
use App\Models\PolicySection;
use App\Models\Review;
use App\Support\StoreCache;
use Illuminate\Support\Collection;

/** Editable site texts, FAQs, reviews and policy pages, read from the memory cache (REQ-DB-25). */
class SiteContent
{
    private ?array $data = null;

    public function __construct(private readonly StoreCache $cache) {}

    private function data(): array
    {
        return $this->data ??= $this->cache->remember('content', fn () => [
            'blocks' => ContentBlock::query()->get(['key', 'value_en', 'value_ar'])->keyBy('key')->all(),
            'faqs' => Faq::query()->where('is_published', true)->orderBy('position')
                ->get(['id', 'question_en', 'question_ar', 'answer_en', 'answer_ar']),
            'reviews' => Review::query()->where('is_published', true)->orderBy('position')
                ->get(['id', 'author', 'country_code', 'image_path', 'body_en', 'body_ar']),
            'policies' => PolicySection::query()->orderBy('position')
                ->get(['id', 'page', 'title_en', 'title_ar', 'body_en', 'body_ar'])->groupBy('page')->all(),
        ]);
    }

    /** The text in the current language; English while the Arabic is not written yet. */
    public function text(string $key): string
    {
        return ($this->data()['blocks'][$key] ?? null)?->t('value') ?? '';
    }

    public function faqs(): Collection
    {
        return $this->data()['faqs'];
    }

    public function reviews(): Collection
    {
        return $this->data()['reviews'];
    }

    public function policy(string $page): Collection
    {
        return $this->data()['policies'][$page] ?? collect();
    }
}
