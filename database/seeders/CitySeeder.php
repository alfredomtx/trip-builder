<?php

namespace Database\Seeders;

use App\Models\City;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    use TruncateTable;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncate('cities');

        City::factory()->create([
            'name' => 'Montreal',
            'code' => 'YMQ',
            'timezone' => 'America/Montreal',
        ]);
        City::factory()->create([
            'name' => 'Vancouver',
            'code' => 'YVR',
            'timezone' => 'America/Vancouver',
        ]);

        City::factory(5)->create();
    }
}
