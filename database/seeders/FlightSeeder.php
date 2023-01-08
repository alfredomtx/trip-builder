<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Flight;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Seeder;
use Faker\Generator;


class FlightSeeder extends Seeder
{
    use TruncateTable;


    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncate('flights');

        self::montrealToVancouver1Pm();

        Flight::factory(5)->create();

    }

    /**
     * Create or return a flight leaving from Montreal at `1 PM` and arriving at Vancouver at `3 PM`.
     * Local times converted to UTC on insert.
     * @return Flight
     */
    public static function montrealToVancouver1Pm()
    {
        $faker = app(Generator::class);

        $montreal = CitySeeder::cityHelper('Montreal', 'YMQ', 'America/Montreal');
        $vancouver = CitySeeder::cityHelper('Vancouver', 'YVR', 'America/Vancouver');

        $montrealAirport = AirportSeeder::airportHelper('Montreal', 'YUL', $montreal->id);
        $vancouverAirport = AirportSeeder::airportHelper('Vancouver', 'YVR', $vancouver->id);

        $airline = Airline::factory()->make([
            'name' => "Air Canada",
            'code' => 'AC',
        ]);
        $airline = Airline::firstOrCreate($airline->toArray());

        $montrealTime = date("H:i", strtotime("01:00 PM"));
        $vancouverTime = date("H:i", strtotime("03:00 PM"));

        return Flight::factory()->create([
            'departure_time' => convert_time_to_utc_from_timezone($montrealTime, 'America/Montreal'),
            'arrival_time' => convert_time_to_utc_from_timezone($vancouverTime, 'America/Vancouver'),
            'airline_id' => $airline->id,
            'departure_airport_id' => $montrealAirport->id,
            'arrival_airport_id' => $vancouverAirport->id,
        ]);
    }
}
