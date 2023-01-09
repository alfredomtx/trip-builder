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

        $trips = $this->getFlightsResponse($flights->items());

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
        $query = $this->getFlightsBaseQuery($filters);
        $flights = match (TripType::from($filters['type'])) {
            TripType::OneWay => $this->oneWayTrip($query, $filters),
            TripType::RoundTrip => $this->roundTrip($query, $filters),
            default => throw new GeneralJsonException('Invalid `trip_type`.', 422),
        };
        return $flights;
    }


    private function roundTrip($query, array $filters)
    {
        $departureAirport = Airport::query()->where('code', $filters['departure_airport'])->first();
        $city = $departureAirport->city()->first();

        dd($query->toSql());

        return $this->paginateQuery($query, $filters);
    }

    private function oneWayTrip($query, array $filters)
    {
        $query->where('departure_date', $filters['departure_date']);
        return $this->paginateQuery($query, $filters);
    }

    private function getFlightsBaseQuery(array $filters)
    {
        $query = Flight::query();

        // joining and filtering with the `departure airport`
        $query->join('airports AS departure_airport', function ($join) use ($filters) {
            $join->on('departure_airport_id', 'departure_airport.id')
                ->where('departure_airport.code', $filters['departure_airport']);
        });

        // joining and filtering with the `arrival airport`
        $query->join('airports AS arrival_airport', function ($join) use ($filters) {
            $join->on('arrival_airport_id', 'arrival_airport.id')
                ->where('arrival_airport.code', $filters['arrival_airport']);
        });
        return $query;
    }

    private function paginateQuery($query, array $filters)
    {
        return $query->paginate($filters['page_size'], ['*'], 'page', $filters['page']);
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
