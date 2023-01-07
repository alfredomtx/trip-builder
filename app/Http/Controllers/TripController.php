<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use DateTime;
use DateTimeZone;
use App\Models\City;
use App\Models\Flight;
use App\Models\Airport;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class TripController extends Controller
{
    /**
     * Search for flights according to search criterias and return a JSON array with results
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function searchFlights(Request $request)
    {
        // Required fields:
        $requiredFields = [
            // Airport code standards have maximum of 4 characters
            'departure_airport' => ['required', 'min:2', 'exists:airports,code'],
            'arrival_airport' => ['required', 'min:2', 'exists:airports,code'],
        ];

        $optionalFields = [
            'departure_time' => ['nullable','date_format:H:i'],
            'paginate' => ['nullable', 'bool'],
        ];

        $filters = $request->validate(array_merge($requiredFields, $optionalFields));

        $departureAirport = Airport::where('code', $filters['departure_airport'])->first();
        $city = $departureAirport->city()->first();

        /*
            The requested times are expected to be in UTC timezone, so we need to convert the times
            to the timezone of the respective cities of `departure` and `arrival` airports.

            E.g: A flight departing from `Montreal` at 1 PM, will arrive in `Vancouver` at 3 PM, despite
            the fact the the flight is around 5 hours long.
        */
        if ($filters['departure_time'] ?? false){
            $filters['departure_time'] = $this->convert_time_from_timezone_to_utc($filters['departure_time'], $city->timezone);
        }
        // $filters['arrival_time'] = $this->convert_time_from_timezone_to_utc($filters['arrival_time'], $city->timezone);

        $flights = Flight::filter($filters)
            ->paginate(10);

            $flights->through(function ($value) {

                die(print_r($value));
                // Your code here
                return $value;
            });

        $countBefore = count($flights->getCollection());
        $updatedItems = $flights->getCollection();

        

        $flightsResponse = $this->getFlightsResponse($flights);

        die(print_r($flights));


            
        $flights->setCollection($updatedItems);

        $countAfter = count($flights->getCollection());
        if ($countBefore != $countAfter){
            throw new exception('test');
        }



        return response(200, $flightsResponse);
    }

    /**
     * @param $flights
     * @return array
     */
    private function getFlightsResponse($flights): array {
        /*
            "flights": [{
                "airline": "AC",
                "number": "301",
                "departure_airport": "YUL",
                "departure_datetime": "2021-02-01 07:35",
                "arrival_airport": "YVR",
                "arrival_datetime": "2021-02-01 10:05",
                "price": "373.23"
            },
            {
                ...
            }
        ]
        */

        $formattedFlights = [];
        $totalPrice = 0;
        foreach($flights as $flight){
            $price = $this->formatPriceTwoDecimalPlaces($flight->price);
            $flightData = [
                'airline' => $flight->airline()->first()->code,
                'number' => $flight->number,
                'departure_airport' => $flight->departureAirport()->first()->code,
                'departure_datetime' => $this->formatFlightDatetime($flight->departure_time),
                'arrival_airport' => $flight->arrivalAirport()->first()->code,
                'arrival_datetime' => $this->formatFlightDatetime($flight->arrival_time),
                'price' => $price,
            ];

            $formattedFlights[] = $flightData;
            $totalPrice += $price;
        }

        $flightsResponse = [
            'price' => $this->formatPriceTwoDecimalPlaces((string) $totalPrice),
            'flights' => $formattedFlights
        ];

        return $flightsResponse;
    }

    /**
     * Format the flight time with today's date
     * @param string $time
     * @return string
     */
    private function formatFlightDatetime(string $time): string {
        $date = DateTime::createFromFormat("H:i:s", $time);
        return $date->format("Y-m-d H:i");
    }

    private function formatPriceTwoDecimalPlaces(string $price): string {
        return number_format((float) $price, 2, '.', '');
    }

}
