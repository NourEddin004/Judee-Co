<?php

namespace App\Domain\Rack;

use App\Models\RackItem;

interface RackItemRepository
{
    public function save(RackItem $item): RackItem;

    /** @param  list<int>  $ids  every rack item id, in the new order */
    public function reorder(array $ids): void;

    public function nextPosition(): int;
}
