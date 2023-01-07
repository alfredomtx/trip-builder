<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\City;
use App\Models\Flight;
use App\Models\Airline;
use App\Models\Airport;
use Database\Seeders\AirportSeeder;
use Database\Seeders\FlightSeeder;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

class TripTest extends TestCase
{

    use WithFaker;
    private static $requiredFields = [
        'departure_airport',
        'arrival_airport',
    ];


    public function test_request_missing_required_fields_returns_422(){

        foreach (Self::$requiredFields as $removedField){
            // Arrange
            $fields = array_values(Self::$requiredFields);
            unset($fields[array_search($removedField, $fields)]);

            // Act
            $response = $this->postJson('/api/trips/search');

            // Assert
            $response->assertStatus(422);
            $response->assertJsonPath('message', 'The given data was invalid.');
        }
    }

    /**
     * 
     */
    public function test_departure_and_arrival_filters_work_for_one_round_trip(){
        // Arrange
        // create some random flights to ensure diversification
        Flight::factory(5)->create();
        // create the flight we will actually search for
        $flight = FlightSeeder::montrealToVancouver1Pm();

        $request = [
            'departure_airport' => $flight->departureAirport()->first()->code,
            'arrival_airport' => $flight->arrivalAirport()->first()->code,
            'paginate' => false,
        ];

        // Act
        $response = $this->post('/api/trips/search', $request);
        $flightsResponse = json_decode($response->getContent(), true);
        dd($flightsResponse);

        // Assert
        $response->assertStatus(200);

        // filter from the response the $departureFlight
        $filteredFlights = array_filter($flightsResponse, function ($flightResponse) use ($flight) {
            if ($flightResponse['id'] == $flight->id){
                 return true;
            }
            return false;
        });

        // print_r($flight);
        dd($filteredFlights);

        // assert that at least one flight has been returned
        $this->assertTrue(count($filteredFlights) > 1);

        $flight = reset($filteredFlights);
        $this->assertEquals($flight->number, $flight['number']);

        // Clean
        Flight::query()->delete();
    }

}

