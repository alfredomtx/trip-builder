<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Flight;
use Illuminate\Database\Seeder;

class FlightSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {

        self::montrealToVancouver1Pm();

        Flight::factory(5)->create();

    }

    /**
     * Create or return a flight leaving from Montreal at `1 PM` and arriving at Vancouver at `3 PM`.
     * Local times converted to UTC on insert.
     * @return Flight
     */
    public static function montrealToVancouver1Pm(): Flight {
        $montreal = City::where('code', 'YMQ')->get()->first();
        if (!$montreal){
            $montreal = City::factory()->create([
                'name' => 'Montreal',
                'code' => 'YMQ',
                'timezone' => 'America/Montreal',
            ]);
        }
        
        $montrealAirport = Airport::where('code', 'YUL')->get()->first();
        if (!$montrealAirport){
            $montrealAirport = Airport::factory()
                ->create([
                    'name' => 'Pierre Elliott Trudeau International',
                    'code' => 'YUL',
                    'city_id' => $montreal->id,
                ]);
        }

        $vancouver = City::where('code', 'YVR')->get()->first();
        if (!$vancouver){
            $vancouver = City::factory()->create([
                'name' => 'Vancouver',
                'code' => 'YVR',
                'timezone' => 'America/Vancouver',
            ]);
        }

        $vancouverAirport = Airport::where('code', 'YVR')->get()->first();
        if (!$vancouverAirport){
            $vancouverAirport = Airport::factory()
                ->create([
                    'name' => 'Vancouver International',
                    'code' => 'YVR',
                    'city_id' => $vancouver->id,
                ]);
        }

        $airline = Airline::where('code', 'AC')->get()->first();
        if (!$airline){
            $airline = Airline::factory()->create([
                'name' => "Air Canada",
                'code' => 'AC',
            ]);
        }

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
