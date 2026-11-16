<?php

namespace App\Domain\Rack;

use App\Models\RackItem;

/** Moves a garment one place up or down the rail: the phone-friendly way to reorder (REQ-RCK-08). */
class MoveRackItem
{
    public function __construct(private readonly RackItemRepository $items) {}

    public function __invoke(RackItem $item, int $direction): void
    {
        $ids = RackItem::query()->orderBy('position')->orderBy('id')->pluck('id')->all();
        $from = array_search($item->getKey(), $ids, true);
        $to = $from + ($direction < 0 ? -1 : 1);

        if ($from === false || ! isset($ids[$to])) {
            return;
        }

        [$ids[$from], $ids[$to]] = [$ids[$to], $ids[$from]];
        $this->items->reorder($ids);
    }
}
