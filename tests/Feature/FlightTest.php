<?php
namespace Tests\Feature;

use App\Http\Responses\FlightResponse;
use Tests\TestCase;
use App\Models\Flight;
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
        // params of the search
        $departureAirport = 'YUL';
        $arrivalAirport = 'YVR';
        $departureDate = '2021-02-01';
        // direct flight that we will check
        $directTripDummy = [
           'price' => '273.23',
           'flight' => FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "07:35",
               "10:05", '273.23', 301, $departureDate),
        ];
        // otther flights that we will check
        $dummiesTrip = $this->addDummyTrips($departureDate);

        $assertData = [];
        for ($i = 0; $i < 2; $i++){
            $assertData[$i]['totalPrices'] = number_format($dummiesTrip[$i][0]->price + $dummiesTrip[$i][1]->price, 2);
            $assertData[$i]['dummies'][] = $dummiesTrip[$i][0];
            $assertData[$i]['dummies'][] = $dummiesTrip[$i][1];
        }

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
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // number of trips
        $this->assertTrue(count($data) == 3);

        // validate `direct trip` dummy
        // price
        $this->assertEquals($data[0]['price'], $directTripDummy['price']);
        // attributes
        $this->assertFlightWithDummy($data[0]['flights'][0], $directTripDummy['flight']);

        // remove the first element of the array, which is the `direct trip` we asserted above
        array_splice($data, 0, 1);

        // loop through each trip returned and validate the flight data
        for ($tripCounter = 0; $tripCounter < 2; $tripCounter++){
            $trip = $data[$tripCounter];
            // validate price
            $this->assertEquals($trip['price'], $assertData[$tripCounter]['totalPrices']);
            // attributes
            $this->assertFlightWithDummy($trip['flights'][$tripCounter], $dummiesTrip[$tripCounter][$tripCounter]);
        }
    }

    /**
     * Test if only direct flights are returned with the `stops` param is zero.
     * In this scenario, we are looking for direct flights from Montreal -> Vancouver.
     */
    private function test_one_way_trip_with_direct_flight_only()
    {
        // Arrange
        // params of the search
        $departureAirport = 'YUL';
        $arrivalAirport = 'YVR';
        $departureDate = '2021-02-01';
        // direct flight that we will check
        $directTripsDummy = [];
        $directTripsDummy[] = [
            'price' => '273.23',
            'flight' => FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "07:35",
                "10:05", '273.23', 301, $departureDate),
        ];
        // same flight but later
        $directTripsDummy[] = [
            'price' => '400.23',
            'flight' => FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "09:35",
                "12:05", '400.23', 302, $departureDate),
        ];
        // additional flights
        $this->addDummyTrips($departureDate);

        $params = [
            'departure_airport' => $departureAirport,
            'arrival_airport' => $arrivalAirport,
            'departure_date' => $departureDate,
            'type' => 'one-way',
            'stops' => 0,
        ];

        // Act
        $response = $this->json('GET', self::URI . '/search', $params);

        // Assert
        $data = $response->assertStatus(200)->json('data');
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // number of trips
        $this->assertTrue(count($data) == 2);

        $i = 0;
        foreach ($directTripsDummy as $tripDummy){
            // price
            $this->assertEquals($data[$i]['price'], $tripDummy['price']);
            // attributes
            $this->assertFlightWithDummy($data[$i]['flights'][$i], $tripDummy['flight']);
            $i++;
        }
    }

    private function addDummyTrips($departureDate)
    {
        $dummiesTrip = [];
        $dummiesTrip[0][] = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::cornwallAirport(), "07:35",
            "09:30", '146.42', 317, $departureDate);
        $dummiesTrip[0][] = FlightSeeder::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::vancouverAirport(), "10:10",
            "10:38", '86.23', 318, $departureDate);
        $dummiesTrip[1][] = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::torontoAirport(), "05:00",
            "07:00", '273.23', 398, $departureDate);
        $dummiesTrip[1][] = FlightSeeder::flightHelper(AirportSeeder::torontoAirport(), AirportSeeder::vancouverAirport(), "08:00",
            "11:00", '400.00', 399, $departureDate);
        return $dummiesTrip;

    }
    private function assertFlightWithDummy(array $flight, Flight $dummyFlight)
    {
        // convert the `Flight` to `FlightResponse` to validate all its attributes
        $dummyFlightResponse = FlightResponse::fromFlight($dummyFlight)->toArray();
        // loop through every attribute of `FlightResponse` and validate if they are the same
        foreach (FlightResponse::getAttributes() as $attribute){
            // attributes in json response are snake case
            $attribute = camel_case_to_snake($attribute->name);
            $this->assertSame($dummyFlightResponse[$attribute], $flight[$attribute]
                , "Attribute $attribute is not the same:\n{$dummyFlightResponse[$attribute]} = {$flight[$attribute]}."
            );
        }
    }

    public function test_request_missing_required_fields_returns_422()
    {
        $requiredFields = [
            'departure_airport' => Airport::factory()->create()->code,
            'arrival_airport' => Airport::factory()->create()->code,
            'type' => 'one-way',
            'departure_date' => $this->faker->date(),
        ];

        foreach ($requiredFields as $requiredField => $value){
            // Arrange
            // send params empty
            $params = [];
            foreach($requiredFields as $key => $value){
                $params[$key] = $value;
            }
            // removing the required field from the params array
            unset($params[$requiredField]);

            // Act
            $response = $this->json('GET', self::URI . '/search', $params);

            // Assert
            $this->assertInvalidResponseMissingField($requiredField, $response);
        }

        // Arrange 2
        // `return_date` is required if `type` is "round-trip".
        $requiredField = 'return-date';
        $params = [];
        foreach($requiredFields as $key => $value){
            $params[$key] = $value;
        }
        $params['type'] = 'round-trip';

        // Act 2
        $response = $this->json('GET', self::URI . '/search', $params);

        // Assert 2
        $this->assertInvalidResponseMissingField($requiredField, $response);
    }

    private function assertInvalidResponseMissingField(string $field, $response)
    {
        $this->assertEquals(422, $response->status(),
            "The API did not fail with status 422 when missing field `{$field}`."
        );
        $response->assertJsonPath('message', 'The given data was invalid.');
    }


}

