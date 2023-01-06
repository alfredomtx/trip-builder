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
        $city1 = City::factory()->create();
        $city2 = City::factory()->create();
        $airport1 = Airport::factory()->create(['city_id' => $city1->id]);
        $airport2 = Airport::factory()->create(['city_id' => $city2->id]);
        $airline = Airline::factory()->create();

        Flight::factory(5)->create([
            'airline_id' => $airline->id,
            'departure_airport_id' => $airport1->id,
            'arrival_airport_id' => $airport2->id,
        ]);
    }
}
