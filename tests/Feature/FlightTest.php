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
        $this->assertEquals(3, count($data));

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
     *
     * Trip 1: flight number 301
     * Trip 2: flight number 302
     */
    public function test_one_way_trip_with_direct_flight_only()
    {
        // Arrange
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
        // additional flights, for diversity
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
//        dd($data);
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // number of trips
        $this->assertEquals(count($directTripsDummy), count($data));

        $i = 0;
        foreach ($directTripsDummy as $trip){
            // price
            $this->assertEquals($data[$i]['price'], $trip['price']);
            // attributes
            $this->assertFlightWithDummy($data[$i]['flights'][0], $trip['flight']);
            $i++;
        }
    }

    /**
     * This test is for another specific scenario and is quite extensive, a `round-trip` search with **direct flights only**.
     *
     * It's a search from flights from Montreal -> Vancouver, and the results are 4 trips, each trips has 2 flights, 1
     * is the departure and the other the return flight.
     * In this scenario we are testing the option of having multiple flights to the destination, and multiple flights
     * to return.
     *
     * Expected flight
     * Trip 1: Montreal -> Vancouver (flight number 301) | Vancouver -> Montreal (flight number 302)
     * Trip 2: Montreal -> Vancouver (flight number 301) | Vancouver -> Montreal (flight number 304)
     * Trip 3: Montreal -> Vancouver (flight number 499) | Vancouver -> Montreal (flight number 302)
     * Trip 4: Montreal -> Vancouver (flight number 499) | Vancouver -> Montreal (flight number 304)
     */
    public function test_round_trip_with_direct_flights_only()
    {
        $departureAirport = 'YUL';
        $arrivalAirport = 'YVR';
        $departureDate = '2021-02-01';
        $returnDate = '2021-02-20';

        $flight301 = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "07:35",
            "10:05", '273.23', 301, $departureDate);
        $flight302 = FlightSeeder::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::montrealAirport(), "11:30",
            "19:11", '320.63', 302, $returnDate);
        $flight304 = FlightSeeder::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::montrealAirport(), "12:30",
            "20:11", '550.63', 304, $returnDate);
        $flight499 = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "09:35",
            "12:05", '333.23', 499, $departureDate);

        $dummyTrips = [];
        $dummyTrips[0]['departure'] = $flight301;
        $dummyTrips[0]['return'] = $flight302;
        $dummyTrips[1]['departure'] = $flight301;
        $dummyTrips[1]['return'] = $flight304;
        $dummyTrips[2]['departure'] = $flight499;
        $dummyTrips[2]['return'] = $flight302;
        $dummyTrips[3]['departure'] = $flight499;
        $dummyTrips[3]['return'] = $flight304;

        $totalPricesPerTrip = [];
        for($i = 0; $i < count($dummyTrips) - 1; $i++){
            $totalPricesPerTrip[$i] = 0;
            foreach ($dummyTrips[$i] as $flight){
                $totalPricesPerTrip[$i] += $flight->price;
            }
        }

        $params = [
            'departure_airport' => $departureAirport,
            'arrival_airport' => $arrivalAirport,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'type' => 'round-trip',
            'stops' => 0,
        ];

        // Act
        $response = $this->json('GET', self::URI . '/search', $params);

        // Assert
        $data = $response->assertStatus(200)->json('data');
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // number of trips
        $this->assertEquals(count($dummyTrips), count($data));

        // loop through each trip returned and validate the flight data
        for ($tripCounter = 0; $tripCounter < count($dummyTrips) - 1; $tripCounter++){
            $trip = $data[$tripCounter];
//            dd($trip);
            // validate price
//            dd($totalPricesPerTrip);
            $this->assertEquals($trip['price'], $totalPricesPerTrip[$tripCounter]);
            // attributes
            $departureFlight = $trip['flights'][0];
            $returnFlight = $trip['flights'][1];
            $this->assertFlightWithDummy($departureFlight, $dummyTrips[$tripCounter]['departure']);
            $this->assertFlightWithDummy($returnFlight, $dummyTrips[$tripCounter]['return']);
        }


    }

    /**
     * This test is for another specific scenario and is quite extensive, a `round-trip`..
     *
     * It's a search from flights from Montreal -> Vancouver, and the results are 2 trips, the first being a direct flight trip,
     * the second being a multi stop trip.
     *
     * Trip 1:
     *      - Departure flight: Montreal -> Vancouver (flight number 301)
     *      - Return flight:  Vancouver -> Montreal (flight number 302)
     * Trip 2:
     *      - Departure flight 1: Montreal -> Cornwall (flight number 317)
     *      - Departure flight 2: Cornwall -> Vancouver (flight number 318)
     *      - Return flight 1: Vancouver -> Cornwall (flight number 346)
     *      - Return flight 2: Cornwall -> Montreal (flight number 347)
     */
    public function test_round_trip_with_direct_and_stop_flights()
    {
        $departureAirport = 'YUL';
        $arrivalAirport = 'YVR';
        $departureDate = '2021-02-01';
        $returnDate = '2021-02-20';

        $flight301 = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::vancouverAirport(), "07:35",
            "10:05", '373.23', 301, $departureDate);
        $flight302 = FlightSeeder::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::montrealAirport(), "11:30",
            "19:11", '320.63', 302, $returnDate);
        $flight317 = FlightSeeder::flightHelper(AirportSeeder::montrealAirport(), AirportSeeder::cornwallAirport(), "07:35",
            "09:30", '146.42', 317, $departureDate);
        $flight318 = FlightSeeder::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::vancouverAirport(), "10:10",
            "10:38", '86.23', 318, $departureDate);
        $flight346 = FlightSeeder::flightHelper(AirportSeeder::vancouverAirport(), AirportSeeder::cornwallAirport(), "11:30",
            "18:35", '75.41', 346, $returnDate);
        $flight347 = FlightSeeder::flightHelper(AirportSeeder::cornwallAirport(), AirportSeeder::montrealAirport(), "19:15",
            "19:46", '150.56', 347, $returnDate);

        $directDummyTrip = [];
        $directDummyTrip['departureFlight'] = $flight301;
        $directDummyTrip['returnFlight'] = $flight302;
        $directDummyTrip['price'] = $flight301->price + $flight302->price;

        $dummyTrip = [];
        $dummyTrip[] = $flight317;
        $dummyTrip[] = $flight318;
        $dummyTrip[] = $flight346;
        $dummyTrip[] = $flight347;
        $dummyTrip['price'] = 0;

        for($i = 0; $i < 4; $i++){
            $dummyTrip['price'] += number_format($dummyTrip[$i]->price, 2);
        }

        $params = [
            'departure_airport' => $departureAirport,
            'arrival_airport' => $arrivalAirport,
            'departure_date' => $departureDate,
            'return_date' => $returnDate,
            'type' => 'round-trip',
        ];

        // Act
        $response = $this->json('GET', self::URI . '/search', $params);

        // Assert
        $data = $response->assertStatus(200)->json('data');
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // number of trips
        $this->assertEquals(2, count($data));

        // validate direct flight
        // price
        $trip = $data[0];
        $this->assertEquals($trip['price'], $directDummyTrip['price']);
        // attributes
        $departureFlight = $trip['flights'][0];
        $returnFlight = $trip['flights'][1];
        $this->assertFlightWithDummy($departureFlight, $directDummyTrip['departureFlight']);
        $this->assertFlightWithDummy($returnFlight, $directDummyTrip['returnFlight']);

        // remove the first element of the array, which is the `direct trip` we asserted above
        array_splice($data, 0, 1);

        // validate price
        $this->assertEquals($data[0]['price'], number_format($dummyTrip['price'], 2));
        $trip = $data[0];

        // loop through each trip returned and validate the flight data
        for ($flightCounter = 0; $flightCounter < 4; $flightCounter++){
            // attributes
            $returnFlight = $trip['flights'][$flightCounter];
            $this->assertFlightWithDummy($trip['flights'][$flightCounter], $dummyTrip[$flightCounter]);
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

