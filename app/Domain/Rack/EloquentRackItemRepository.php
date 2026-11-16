<?php

namespace App\Domain\Rack;

use App\Models\RackItem;
use Illuminate\Support\Facades\DB;

class EloquentRackItemRepository implements RackItemRepository
{
    public function save(RackItem $item): RackItem
    {
        $item->save();

        return $item;
    }

    public function reorder(array $ids): void
    {
        DB::transaction(function () use ($ids) {
            foreach (array_values($ids) as $position => $id) {
                RackItem::query()->whereKey($id)->first()?->update(['position' => $position]);
            }
        });
    }

    public function nextPosition(): int
    {
        return (int) RackItem::query()->max('position') + 1;
    }
}
