<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FlightRequest;
use App\Http\Resources\AirlineResource;
use App\Http\Resources\FlightResource;
use App\Models\Airline;
use App\Services\FlightService;
use DateTime;
use DateTimeZone;
use App\Models\City;
use App\Models\Flight;
use App\Models\Airport;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Support\Facades\DB;

class FlightController extends Controller
{
    /**
     * Search for flights according to search criteria and return paginated result.
     *
     * @param FlightRequest $request
     * @param FlightService $service
     * @return ResourceCollection
     */
    public function searchFlights(FlightRequest $request, FlightService $service)
    {
        $filters = $request->validated();

        $flights = $service->searchFlights($filters);

        return FlightResource::collection($flights);
    }

    /**
     * @param $flights
     * @return array
     */
    private function getFlightsResponse($flights)
    {
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
    private function formatFlightDatetime(string $time) {
        $date = DateTime::createFromFormat("H:i:s", $time);
        return $date->format("Y-m-d H:i");
    }

    private function formatPriceTwoDecimalPlaces(string $price) {
        return number_format((float) $price, 2, '.', '');
    }

}
