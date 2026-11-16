<?php

namespace Database\Seeders;

use App\Models\DeliveryCity;
use Illuminate\Database\Seeder;

/** The 12 cities, fees and 215 areas of Appendix D.8.2 (REQ-DMO-88), and the 6 Gulf countries (SRS 1.4). */
class DeliverySeeder extends Seeder
{
    public function run(): void
    {
        foreach (SeedData::catalog()['cities'] as $position => $row) {
            $city = DeliveryCity::updateOrCreate(['key' => $row['key']], [
                'country' => 'JO', 'name_en' => $row['name_en'], 'name_ar' => $row['name_ar'],
                'fee' => $row['fee'], 'is_active' => true, 'position' => $position,
            ]);

            $city->areas()->delete();
            $city->areas()->createMany(array_map(
                fn ($area, $index) => ['name_en' => $area['en'], 'name_ar' => $area['ar'], 'position' => $index],
                $row['areas'],
                array_keys($row['areas']),
            ));
        }

        $position = count(SeedData::catalog()['cities']);
        foreach (config('store.gulf_countries') as $row) {
            DeliveryCity::updateOrCreate(['key' => $row['key']], $row + ['fee' => 25, 'is_active' => true, 'position' => $position++]);
        }
    }
}
