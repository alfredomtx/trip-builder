<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\City;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirportSeeder extends Seeder
{
    use TruncateTable;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncate('airports');

        Airport::factory()->create([
            'name' => 'Pierre Elliott Trudeau International',
            'code' => 'YUL',
            'city_id' => 1,
        ]);
        Airport::factory()->create([
            'name' => 'Vancouver International',
            'code' => 'YVR',
            'city_id' => 2,
        ]);

        Airport::factory(5)->create();
    }
}
