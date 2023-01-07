<?php

namespace App\Services;

use App\Models\Airport;
use App\Models\Flight;

class FlightService
{

    public function searchFlights(array $filters)
    {
        $departureAirport = Airport::query()->where('code', $filters['departure_airport'])->first();
        $city = $departureAirport->city()->first();

        /*
            The requested times are expected to be in UTC timezone, so we need to convert the times
            to the timezone of the respective cities of `departure` and `arrival` airports.

            E.g: A flight departing from `Montreal` at 1 PM, will arrive in `Vancouver` at 3 PM, despite
            the fact the flight is around 5 hours long.
        */
        if ($filters['departure_time'] ?? false){
            $filters['departure_time'] = $this->convert_time_from_timezone_to_utc($filters['departure_time'], $city->timezone);
        }
        // $filters['arrival_time'] = $this->convert_time_from_timezone_to_utc($filters['arrival_time'], $city->timezone);

        $pageSize= $filters['page_size'] ?? 10;
        $flights = Flight::filter($filters)
            ->paginate($pageSize);

        return $flights;
    }

}
