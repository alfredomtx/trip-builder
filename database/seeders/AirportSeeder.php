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

        self::montrealAirport();
        self::cornwallAirport();
        self::torontoAirport();
        self::vancouverAirport();


//        Airport::factory(5)->create();
    }

    public static function torontoAirport()
    {
        $city = CitySeeder::cityHelper('Toronto', 'YYZ');
        return self::airportHelper('Toronto Pearson International Airport', 'YYZ', $city->id, 'America/Toronto');
    }

    public static function cornwallAirport()
    {
        $city = CitySeeder::cityHelper('Cornwall', 'YCC');
        return self::airportHelper('Cornwall Regional Airport', 'YCC', $city->id, 'America/Toronto');
    }

    public static function montrealAirport()
    {
        $city = CitySeeder::cityHelper('Montreal', 'YMQ');
        return self::airportHelper('Pierre Elliott Trudeau International Airport', 'YUL', $city->id, 'America/Montreal');
    }

    public static function vancouverAirport()
    {
        $city = CitySeeder::cityHelper('Vancouver', 'YVR');
        return self::airportHelper('Vancouver International Airport', 'YVR', $city->id, 'America/Vancouver');
    }

    public static function airportHelper(string $name, string $code, int $cityId, string $timezone)
    {
        $airport = Airport::where('code', $code)->first();
        if ($airport){
            return $airport;
        }
        return  Airport::factory()->create([
            'name' => $name,
            'code' => $code,
            'city_id' => $cityId,
            'timezone' => $timezone,
        ]);
    }
}
