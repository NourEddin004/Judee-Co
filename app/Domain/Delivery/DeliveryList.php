<?php

namespace App\Domain\Delivery;

use App\Models\DeliveryCity;
use App\Support\StoreCache;
use Illuminate\Support\Collection;

/** Active delivery cities with their areas, cached (REQ-DB-25). */
class DeliveryList
{
    public function __construct(private readonly StoreCache $cache) {}

    public function cities(): Collection
    {
        return $this->cache->remember('delivery', fn () => DeliveryCity::query()
            ->where('is_active', true)
            ->orderBy('position')
            ->with(['areas' => fn ($query) => $query->select(['id', 'delivery_city_id', 'name_en', 'name_ar', 'position'])])
            ->get(['id', 'key', 'country', 'name_en', 'name_ar', 'fee']));
    }

    public function find(string $key): ?DeliveryCity
    {
        return $this->cities()->firstWhere('key', $key);
    }

    /** Active Jordan cities, for the city list (REQ-CHK-27). */
    public function jordanCities(): Collection
    {
        return $this->cities()->filter(fn (DeliveryCity $city) => $city->isJordan())->values();
    }

    /** Active Gulf countries, one row each (REQ-CHK-26). */
    public function gulfCountries(): Collection
    {
        return $this->cities()->reject(fn (DeliveryCity $city) => $city->isJordan())->values();
    }

    /**
     * The fees the checkout lead and bag summary quote, read from the admin's delivery settings:
     * the usual fee across Jordan and the usual Gulf fee ("3 JD across Jordan · 25 JD to Gulf
     * countries", REQ-CHK-32). The Gulf fee is null when no Gulf country is switched on.
     *
     * @return array{jordan: ?string, gulf: ?string}
     */
    public function headlineFees(): array
    {
        $usual = fn (Collection $rows) => $rows->countBy(fn ($row) => (string) $row->fee)->sortDesc()->keys()->first();

        return ['jordan' => $usual($this->jordanCities()), 'gulf' => $usual($this->gulfCountries())];
    }
}
