<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Flight;
use Database\Factories\FlightFactory;
use Database\Seeders\Traits\TruncateTable;
use DateTime;
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
//
//        self::montrealToVancouver("01:00 PM", "03:00 PM");
//        self::montrealToVancouver("03:00 PM", "05:00 PM");
//        self::montrealToVancouver("07:00 PM", "09:00 PM");
//
//        self::montrealToVancouver("01:00 PM", "03:00 PM", $faker->date());
//        self::montrealToVancouver("03:00 PM", "05:00 PM", $faker->date());
//        self::montrealToVancouver("07:00 PM", "09:00 PM", $faker->date());

        $departureDate = '2021-02-01';
        $returnDate = '2021-02-20';
        // flights for one-way trip test
        self::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "07:35",
            "10:05", '273.23', 301, $departureDate);
        self::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::montrealAirport(), "11:30",
            "19:11", '320.63', 302, $returnDate);

        // flights for round trip test
        // departure
        self::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::cornwallAirport(), "07:35",
            "09:30", '146.42', 317, $departureDate);
        self::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::vancouverAirport(), "10:10",
            "10:38", '86.23', 318, $departureDate);
        self::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::vancouverAirport(), "07:00",
            "07:30", '86.23', 320, $departureDate);
//        self::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::vancouverAirport(), "05:00",
//            "06:00", '146.42', 321, $departureDate);
        // return
        self::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::cornwallAirport(), "11:30",
            "18:35", '75.41', 346, $returnDate);
        self::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::montrealAirport(), "19:15",
            "19:46", '150.56', 347, $returnDate);

        self::flightHelper(AirportSeeder::torontoAirport(), AirportSeeder::vancouverAirport(), "08:00",
            "11:00", '400.00', 399, $departureDate);

        self::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::torontoAirport(), "05:00",
            "07:00", '273.23', 398, $departureDate);
//        $today = new DateTime();
//        $tomorrow = $today->modify('+1 day')->format('Y-m-d');
//        self::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::montrealAirport(), "01:00", "08:00", $tomorrow);

//        self::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::torontoAirport(), "01:00", "03:00");
//        self::flightHelper(AirportSeeder::torontoAirport(), AirportSeeder::vancouverAirport(), "04:00", "5:00");

//        Flight::factory(5)->create();

    }

    public static function flightHelper(Airport $airportFrom, Airport $airportTo, string $departureTime, string $arrivalTime, string $price, int $number, string $date = null)
    {
        $airline = Airline::factory()->make([
            'name' => "Air Canada",
            'code' => 'AC',
        ]);
        $airline = Airline::firstOrCreate($airline->toArray());

        $date = ($date === null) ? date('Y-m-d') : $date;
        return Flight::factory()->create([
            'number' => $number,
            'price' => $price,
            'departure_date' => $date,
            'arrival_date' => $date,
            'departure_time' => date("H:i:s", strtotime($departureTime)),
            'arrival_time' => date("H:i:s", strtotime($arrivalTime)),
            'airline_id' => $airline->id,
            'departure_airport_id' => $airportFrom->id,
            'arrival_airport_id' => $airportTo->id,
        ]);
    }

    /**
     * Create a flight leaving from Montreal at `1 PM` and arriving at Vancouver at `3 PM`.
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
            'arrival_time' => convert_time_to_utc_from_timezone($vancouverTime, 'America/Vancouver'),
            'airline_id' => $airline->id,
            'departure_airport_id' => $montrealAirport->id,
            'arrival_airport_id' => $vancouverAirport->id,
        ]);
    }
}
