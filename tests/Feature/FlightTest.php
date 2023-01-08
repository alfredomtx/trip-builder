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

class FlightTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    protected array $requiredFields = [
        'departure_airport',
        'arrival_airport',
    ];

    public function test_departure_and_arrival_filters_work_for_one_round_trip()
    {
        // Arrange
        Flight::factory(5)->create();
        // create the flight we will actually search for
        $flight = FlightSeeder::montrealToVancouver1Pm();

        $params = [
            'departure_airport' => $flight->departureAirport()->first()->code,
            'arrival_airport' => $flight->arrivalAirport()->first()->code,
        ];

        // Act
        $response = $this->json('GET', '/api/flights/search', $params);

        // Assert
        $data = $response->assertStatus(200)->json('data');
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // print_r($flight);
        // assert that at least one flight has been returned
        $this->assertTrue(count($data) > 1);
        // loop through the `flights` returned and find the flight with the ID we expect

        $flight = reset($data);
        $this->assertEquals($flight->number, $flight['number']);

        // Clean
        Flight::query()->delete();
    }

    public function test_departure_and_arrival_filters_work_for_two_round_trip()
    {

    }


    public function test_request_missing_required_fields_returns_422()
    {
        // TODO: get fillables
        foreach ($this->requiredFields as $removedField){
            // Arrange
            $fields = array_values($this->requiredFields);
            unset($fields[array_search($removedField, $fields)]);

            // Act
            $response = $this->json('GET', '/api/flights/search', $fields);

            // Assert
            $this->assertEquals(422, $response->status(),
                "The API did not fail with status 422 when missing field `{$removedField}`."
            );
            $response->assertJsonPath('message', 'The given data was invalid.');
        }
    }


}

