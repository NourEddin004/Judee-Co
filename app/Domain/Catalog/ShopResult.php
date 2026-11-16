<?php

namespace App\Domain\Catalog;

use App\Models\Category;
use Illuminate\Support\Collection;

final class ShopResult
{
    /**
     * @param  list<string>  $selectedShades  shade keys
     * @param  list<string>  $selectedSizes  size labels, as the owner wrote them
     */
    public function __construct(
        public readonly ?Category $category,
        public readonly Collection $products,
        public readonly int $total,
        public readonly int $page,
        public readonly int $pageCount,
        public readonly Collection $availableShades,
        public readonly array $selectedShades,
        public readonly Collection $availableSizes,
        public readonly array $selectedSizes,
        public readonly string $search,
    ) {}

    /** How many filter choices are ticked, for the count on the Filters button (D.5.7). */
    public function filterCount(): int
    {
        return count($this->selectedShades) + count($this->selectedSizes);
    }

    /** Query string for this list on another page, the defaults left out (D.6.2). */
    public function query(array $overrides = []): array
    {
        $values = array_merge([
            'q' => $this->search,
            'shade' => implode(',', $this->selectedShades),
            'size' => mb_strtolower(implode(',', $this->selectedSizes)),
            'page' => $this->page,
        ], $overrides);

        return array_filter([
            'q' => $values['q'] !== '' ? $values['q'] : null,
            'shade' => $values['shade'] !== '' ? $values['shade'] : null,
            'size' => $values['size'] !== '' ? $values['size'] : null,
            'page' => $values['page'] > 1 ? $values['page'] : null,
        ]);
    }

    /**
     * Three page numbers ending at the current page where possible, then a gap and the last page:
     * with 7 pages, page 1 shows 1 2 3 … 7, page 4 shows 2 3 4 … 7, page 7 shows 5 6 7 (REQ-DMO-23).
     *
     * @return array<int, int|null> null marks the gap
     */
    public function pageNumbers(): array
    {
        $window = 3;
        $start = min(max($this->page - ($window - 1), 1), max(1, $this->pageCount - ($window - 1)));
        $end = min($this->pageCount, $start + $window - 1);
        $items = range($start, $end);
        if ($end < $this->pageCount - 1) {
            $items[] = null;
        }
        if ($end < $this->pageCount) {
            $items[] = $this->pageCount;
        }

        return $items;
    }
}
