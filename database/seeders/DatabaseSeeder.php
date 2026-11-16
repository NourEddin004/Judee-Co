<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

/**
 * Starting content for review: the demo's sample catalog, delivery list and texts
 * (Appendix D.8). Arabic values are drafts waiting for the owner's approval (REQ-DMO-87).
 */
class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            SettingsSeeder::class,
            CatalogSeeder::class,
            RackSeeder::class,
            DeliverySeeder::class,
            ContentSeeder::class,
        ]);

        if (app()->environment('local')) {
            $this->call(LocalAdminSeeder::class);
        }
    }
}
