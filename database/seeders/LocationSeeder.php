<?php

namespace Database\Seeders;

use App\Models\Location;
use Illuminate\Database\Seeder;

class LocationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * Only seeds if the locations table has no entries (so users can choose a location at login).
     */
    public function run(): void
    {
        if (Location::count() > 0) {
            return;
        }

        Location::create([
            'active' => true,
            'name' => 'Main Store',
            'code' => 'MAIN-001',
            'description' => 'Default main store location',
            'type' => 'store',
        ]);
    }
}
