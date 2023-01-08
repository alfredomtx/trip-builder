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

        $montreal = CitySeeder::cityHelper('Montreal', 'YMQ', 'America/Montreal');
        $vancouver = CitySeeder::cityHelper('Vancouver', 'YVR', 'America/Vancouver');

        Self::airportHelper('Montreal', 'YUL', $montreal->id);
        Self::airportHelper('Vancouver', 'YVR', $vancouver->id);

//        Airport::factory(5)->create();
    }

    public static function airportHelper(string $name, string $code, int $cityId)
    {
        $airport = Airport::where('code', $code)->first();
        if ($airport){
            return $airport;
        }
        return  Airport::factory()->create([
            'name' => $name,
            'code' => $code,
            'city_id' => $cityId,
        ]);
    }
}
