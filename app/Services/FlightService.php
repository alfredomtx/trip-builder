<?php

namespace App\Services;

use App\Enums\TripType;
use App\Exceptions\GeneralJsonException;
use App\Http\Resources\FlightResource;
use App\Http\Resources\TripResource;
use App\Http\Responses\FlightResponse;
use App\Http\Responses\TripResponse;
use App\Models\Airport;
use App\Models\Flight;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\Paginator;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;


class FlightService
{

    /**
     * @param array $filters
     * @return ResourceCollection
     * @throws GeneralJsonException
     */
    public function searchFlights(array $filters)
    {
        $filters['page_size'] = $filters['page_size'] ?? 10;
        $filters['page'] = $filters['page'] ?? 1;

        $flights = $this->getFlights($filters);

        $trips = $this->getFlightsResponse($flights));

        $paginatedTrips = $this->getPaginatedTrips($trips, $flights);
        return TripResource::collection($paginatedTrips);
    }

    /**
     * @param array $filters
     * @return Collection
     * @throws GeneralJsonException
     */
    private function getFlights(array $filters)
    {
        $flights = match (TripType::from($filters['type'])) {
            TripType::OneWay => $this->oneWayTrip($filters),
            TripType::RoundTrip => $this->roundTrip($filters),
            default => throw new GeneralJsonException('Invalid `trip_type`.', 422),
        };
        return $flights;
    }


    private function roundTrip(array $filters)
    {
        $departureAirport = Airport::query()->where('code', $filters['departure_airport'])->first();
        $city = $departureAirport->city()->first();

        // get flights from departure airport in departure date
        $query = $this->getFlightsBaseQuery($filters['departure_airport'], $filters['arrival_airport'], $filters['departure_date']);
        $departureFlights = $query->get();

        $query = $this->getFlightsBaseQuery($filters['arrival_airport'], $filters['departure_airport'], $filters['return_date']);
        $returnFlights = $query->get();
//        dd($returnFlights->toArray());
        dd($returnFlights->paginate());

        // A trip references one or many flights, each one departing after the arrival date of the previous flight.

        $test = $returnFlights->merge($departureFlights);
//
//        //
//
//        dd($query->all());

        return $test;
    }

    private function oneWayTrip(array $filters)
    {
        // situation 1: Montreal -> Vancouver
        // situation 2: Montreal -> Cornall -> Vancouver

        /*
         * Select all flights where:
         * - `airport_arrival` is YVR
         * - `departure_date` is 2022-02-01
         *
         * it might bring for example:
         * - Flight 1: Cornwall -> Vancouver
         * - Flight 2: Montreal -> Vancouver
         */

        /*
         * From the selected flights, filter:
         * - `arrival_datetime` is still 2022-02-01 in **local** Vancouver
         * - `departure_airport` of the selected flight (Cornwall) has flights coming from `departure_airport`(Montreal)
         * - if has, the `departure_datetime` of the Cornwall -> Vancouver flight must be earlier than Montreal -> Cornall `arrival_time)
         * may be with a margin of 30 minutes?
         *
         * $filter['departure_airport'] =
         *
         */

        // from these flights, filter all which `
        $query = $this->getFlightsBaseQuery($filters['departure_airport'], $filters['arrival_airport'], $filters['departure_date']);
        return $this->paginateQuery($query, $filters['page_size'], $filters['page']);
    }

    private function getFlightsBaseQuery(string $departureAirportCode, string $arrivalAirportCode, string $departureDate)
    {
        $query = Flight::query();

        // joining and filtering with the `departure airport`
        $query->join('airports AS departure_airport', function ($join) use ($departureAirportCode) {
            $join->on('departure_airport_id', 'departure_airport.id')
                ->where('departure_airport.code', $departureAirportCode);
        });

        // joining and filtering with the `arrival airport`
        $query->join('airports AS arrival_airport', function ($join) use ($arrivalAirportCode) {
            $join->on('arrival_airport_id', 'arrival_airport.id')
                ->where('arrival_airport.code', $arrivalAirportCode);
        });

        $query->where('departure_date', $departureDate);

        return $query;
    }

    private function paginateQuery($query, int $pageSize, int $page)
    {
        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * @param $trips
     * @param $flights
     * @return LengthAwarePaginator
     */
    private function getPaginatedTrips($trips, $flights)
    {
        $trips = new LengthAwarePaginator($trips->toArray(), $flights->total(), $flights->perPage());
        $trips->withPath(Paginator::resolveCurrentPath());
        return $trips;
    }


    /**
     * Get a collection of `TripResponse`
     *
     * @param array $flights
     * @return Collection
     */
    private function getFlightsResponse(array $flights)
    {
        // convert the flights returned from database to a Collection of `FlightResponse`
        $flightsResponseCollection = collect($flights)->map(function ($flight) {
            return FlightResponse::fromFlight($flight);
        });

        // convert the collection of FlightResponse to a collection of `TripResponse`
        $tripsResponse =  $flightsResponseCollection->map(function ($flightResponse) {
            $tripResponse = new TripResponse();
            $tripResponse->addPrice(floatval($flightResponse->getPrice()))
                ->addFlight($flightResponse);
            return $tripResponse;
        });

        return $tripsResponse;
    }





}
