<?php

namespace Tests\Feature;

use App\Http\Responses\FlightResponse;
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

    const URI = '/api/flights';

    protected array $requiredFields = [
        'departure_airport',
        'arrival_airport',
    ];

    public function test_one_way_trip()
    {
        // Arrange
        // create the flight we will actually search for
        Flight::factory(5)->create();
        FlightSeeder::montrealToVancouver("01:00 PM", "03:00 PM", $this->faker->date());
        FlightSeeder::montrealToVancouver("03:00 PM", "0:00 PM", $this->faker->date());
        // flights that we will check
        $dummies[] = FlightSeeder::montrealToVancouver("01:00 PM", "03:00 PM");
        $dummies[] = FlightSeeder::montrealToVancouver("03:00 PM", "05:00 PM");

        $params = [
            'departure_airport' => $dummies[0]->departureAirport()->first()->code,
            'arrival_airport' => $dummies[0]->arrivalAirport()->first()->code,
            'departure_date' => $dummies[0]->departure_date,
            'type' => 'one-way',
        ];

        // Act
        $response = $this->json('GET', self::URI . '/search', $params);

        // Assert
        $data = $response->assertStatus(200)->json('data');
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // same number of flights returned
        $this->assertTrue(count($data) == 2);

        $i = 0;
        // loop through each trip returned and validate the flight data
        foreach ($data as $trip){
            // validate price
            $this->assertEquals($trip['price'], $dummies[$i]->price);

            $flight = reset($trip['flights']);
            $dummyFlightResponse = FlightResponse::fromFlight($dummies[$i])->toArray();
            // loop through every attribute of `FlightResponse` and validate if they are the same
            foreach (FlightResponse::getAttributes() as $attribute){
                // attributes in json response are snake case
                $attribute = camel_case_to_snake($attribute->name);
                $this->assertSame($dummyFlightResponse[$attribute], $flight[$attribute]
                    , "Attribute $attribute is not equal in flight $i:\n{$dummyFlightResponse[$attribute]} = {$flight[$attribute]}."
                );
            }
            $i++;
        };
    }

    // TODO: refactor this test
    public function test_request_missing_required_fields_returns_422()
    {
        // TODO: get fillables
        foreach ($this->requiredFields as $removedField){
            // Arrange
            $fields = array_values($this->requiredFields);
            unset($fields[array_search($removedField, $fields)]);

            // Act
            $response = $this->json('GET', self::URI . '/search', $fields);

            // Assert
            $this->assertEquals(422, $response->status(),
                "The API did not fail with status 422 when missing field `{$removedField}`."
            );
            $response->assertJsonPath('message', 'The given data was invalid.');
        }
    }


}

