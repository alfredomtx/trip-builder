<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Flight;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
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
        $faker = app(Generator::class);

        self::montrealToVancouver("01:00 PM", "03:00 PM");
        self::montrealToVancouver("03:00 PM", "05:00 PM");
        self::montrealToVancouver("07:00 PM", "09:00 PM");

        self::montrealToVancouver("01:00 PM", "03:00 PM", $faker->date());
        self::montrealToVancouver("03:00 PM", "05:00 PM", $faker->date());
        self::montrealToVancouver("07:00 PM", "09:00 PM", $faker->date());

//        Flight::factory(5)->create();

    }

    /**
     * Create or return a flight leaving from Montreal at `1 PM` and arriving at Vancouver at `3 PM`.
     * The departure and arrival dates are the current day.
     * Local times converted to UTC on insert.
     *
     * @param string $departureTimeAmPm Example: 01:00 PM
     * @param string $arrivalTimeAmPm Example: 03:00 PM
     * @param string|null $date If null, will generate with today's date
     * @return Collection|Model
     */
    public static function montrealToVancouver(string $departureTimeAmPm, string $arrivalTimeAmPm, string $date = null)
    {
        $montreal = CitySeeder::cityHelper('Montreal', 'YMQ', 'America/Montreal');
        $vancouver = CitySeeder::cityHelper('Vancouver', 'YVR', 'America/Vancouver');

        $montrealAirport = AirportSeeder::airportHelper('Montreal', 'YUL', $montreal->id);
        $vancouverAirport = AirportSeeder::airportHelper('Vancouver', 'YVR', $vancouver->id);

        $airline = Airline::factory()->make([
            'name' => "Air Canada",
            'code' => 'AC',
        ]);
        $airline = Airline::firstOrCreate($airline->toArray());

        $montrealTime = date("H:i", strtotime($departureTimeAmPm));
        $vancouverTime = date("H:i", strtotime($arrivalTimeAmPm));

        $date = ($date === null) ? date('Y-m-d') : $date;

        return Flight::factory()->create([
            'departure_date' => $date,
            'arrival_date' => $date,
            'departure_time' => convert_time_to_utc_from_timezone($montrealTime, 'America/Montreal'),
            'departure_time' => convert_time_to_utc_from_timezone($montrealTime, 'America/Montreal'),
            'arrival_time' => convert_time_to_utc_from_timezone($vancouverTime, 'America/Vancouver'),
            'airline_id' => $airline->id,
            'departure_airport_id' => $montrealAirport->id,
            'arrival_airport_id' => $vancouverAirport->id,
        ]);
    }
}
