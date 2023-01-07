<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\City;
use Illuminate\Database\Seeder;

class AirportSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        Airport::factory(5)->create();
    }
}
