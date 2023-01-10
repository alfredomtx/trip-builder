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

    /**
     * This test is a bit extensive because it tries to simulate a very specific scenario to test the
     * logic of the search flight algorithm.
     *
     * Briefly, the scenario is a trip search from Montreal -> Vancouver and the result is a multi stop trip with 3
     * options.
     * Trip 1: Montreal -> Vancouver
     * Trip 2: Montreal -> Cornwall -> Vancouver
     * Trip 3: Montreal -> Toronto -> Vancouver
     */
    // TODO: refactor this test, it  completely hideous 😅
    public function test_one_way_trip_with_direct_and_one_stop_flight()
    {
        // Arrange
        // create the flight we will actually search for
        $departureAirport = 'YUL';
        $arrivalAirport = 'YVR';
        $departureDate = '2021-02-01';
        // flights for round trip test
        // departure
        $directTripDummy = [
           'price' => '273.23',
           'flight' => FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "07:35",
               "10:05", '273.23', 301, $departureDate),
        ];
        // flights that we will check
        $dummiesTrip[0][] = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::cornwallAirport(), "07:35",
            "09:30", '146.42', 317, $departureDate);
        $dummiesTrip[0][] = FlightSeeder::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::vancouverAirport(), "10:10",
            "10:38", '86.23', 318, $departureDate);
        $dummiesTrip[1][] = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::torontoAirport(), "05:00",
            "07:00", '273.23', 398, $departureDate);
        $dummiesTrip[1][] = FlightSeeder::flightHelper(AirportSeeder::torontoAirport(), AirportSeeder::vancouverAirport(), "08:00",
            "11:00", '400.00', 399, $departureDate);

        $assertData = [];
        for ($i = 0; $i < 2; $i++){
            $assertData[$i]['totalPrices'] = number_format($dummiesTrip[$i][0]->price + $dummiesTrip[$i][1]->price, 2);
            $assertData[$i]['dummies'][] = $dummiesTrip[$i][0];
            $assertData[$i]['dummies'][] = $dummiesTrip[$i][1];
        }

        // additional random data
        Flight::factory(5)->create();

        $params = [
            'departure_airport' => $departureAirport,
            'arrival_airport' => $arrivalAirport,
            'departure_date' => $departureDate,
            'type' => 'one-way',
        ];

        // Act
        $response = $this->json('GET', self::URI . '/search', $params);

        // Assert
        $data = $response->assertStatus(200)->json('data');
//        dd($data);
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // number of trips
        $this->assertTrue(count($data) == 3);

        // validate direct trip dummy
        $tripCounter = 0;
        $trip = $data[$tripCounter];
        // validate price
        $this->assertEquals($trip['price'], $directTripDummy['price']);

        $flight = $trip['flights'][$tripCounter];
        $dummyFlightResponse = FlightResponse::fromFlight($directTripDummy['flight'])->toArray();
        // loop through every attribute of `FlightResponse` and validate if they are the same
        foreach (FlightResponse::getAttributes() as $attribute){
            // attributes in json response are snake case
            $attribute = camel_case_to_snake($attribute->name);
            $this->assertSame($dummyFlightResponse[$attribute], $flight[$attribute]
                , "[Trip $tripCounter] Attribute $attribute is not equal in flight $tripCounter:\n{$dummyFlightResponse[$attribute]} = {$flight[$attribute]}."
            );
        }

        // remove the first element of the array, which is the direct flight
        array_splice($data, 0, 1);

        // loop through each trip returned and validate the flight data
        for ($tripCounter = 0; $tripCounter < 2; $tripCounter++){
            $trip = $data[$tripCounter];
            // validate price
            $this->assertEquals($trip['price'], $assertData[$tripCounter]['totalPrices']);

            $flight = $trip['flights'][$tripCounter];
            $dummyFlightResponse = FlightResponse::fromFlight($dummiesTrip[$tripCounter][$tripCounter])->toArray();
            // loop through every attribute of `FlightResponse` and validate if they are the same
            foreach (FlightResponse::getAttributes() as $attribute){
                // attributes in json response are snake case
                $attribute = camel_case_to_snake($attribute->name);
                $this->assertSame($dummyFlightResponse[$attribute], $flight[$attribute]
                    , "Attribute $attribute is not equal in flight $tripCounter:\n{$dummyFlightResponse[$attribute]} = {$flight[$attribute]}."
                );
            }
        }
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

