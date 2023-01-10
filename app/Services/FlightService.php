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
use Illuminate\Support\Facades\DB;

class FlightService
{
    /**
     * @param array $filters
     * @return ResourceCollection
     * @throws GeneralJsonException
     */
    public function getFlights(array $filters)
    {
        $filters['page_size'] = $filters['page_size'] ?? 10;
        $filters['page'] = $filters['page'] ?? 1;

        $trips = $this->searchFlights($filters);
//        dd($trips);

//        $trips = $this->getFlightsResponse($flights);

        $paginatedTrips = $this->getPaginatedTrips($trips, $filters['page_size']);
        return TripResource::collection($paginatedTrips);
//        return TripResource::collection($trips);
    }

    /**
     * @param array $filters
     * @return Collection
     * @throws GeneralJsonException
     */
    private function searchFlights(array $filters)
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


        return $test;
    }

    /**
     * @param $originAirport
     * @param $destinationAirport
     * @param $departureDate
     * @return array
     */
    private function getDirectFlights($originAirport, $destinationAirport, $departureDate)
    {
        // get direct flights from origin to destination
        $sql = "
            SELECT flights.*,
                departure_airport.code AS code
                FROM flights

                JOIN airports AS departure_airport ON departure_airport.id = departure_airport_id
                AND departure_airport.code = :departure_airport_code
                JOIN airports AS arrival_airport ON arrival_airport.id = arrival_airport_id
                AND arrival_airport.code = :arrival_airport_code

                WHERE departure_date = :departure_date
        ";

        $directFlights = DB::select(DB::raw($sql), [
            'departure_airport_code' => $originAirport,
            'arrival_airport_code' => $destinationAirport,
            'departure_date' => $departureDate,
        ]);

        // convert all flights to array
        return array_map(fn ($flight) => (array) $flight, $directFlights);
    }

    /**
     * @param array $flights
     * @return Flight[]
     */
    private function convertArrayToFlights(array $flights)
    {
        $array = [];
        foreach($flights as $flight){
            $array[] = Flight::make((array) $flight);
        }
        return $array;
    }

    /**
     * @param array $filters
     * @return Collection
     */
    private function oneWayTrip(array $filters)
    {
        $originAirport = $filters['departure_airport'];
        $destinationAirport = $filters['arrival_airport'];
        $departureDate = $filters['departure_date'];
        $stops = $filters['stops'] ?? null;

        $directFlights = $this->getDirectFlights($originAirport, $destinationAirport, $departureDate);
//        dd($directFlights);
        // TODO: add option to direct flights only, if true, return here
        $directFlightsResponse = $this->getFlightsResponse($this->convertArrayToFlights($directFlights));
//        dd($directFlightsResponse);
        if ($stops === 0){
            return $directFlightsResponse;
        }

        // STEP 1
        $flightsToDestination = $this->selectStopFlightsGoingToDestination($destinationAirport, $departureDate, $directFlights);
//        dd($flightsToDestination);

        // STEP 2
        $flightsToStopDestinations = [];
        foreach ($flightsToDestination as $airport => $flights){
            if (!isset($flightsToStopDestinations[$airport])){
                $flightsToStopDestinations[$airport] = [];
            }
            $flightsToStopDestinations[$airport] = $this->getDirectFlights($originAirport, $airport, $departureDate);
        }

        // STEP 3
        $trips = [];
        foreach ($flightsToStopDestinations as $airport => $flights)
        {
            if (!isset($trips[$airport])){
                $trips[$airport] = [];
            }

            foreach ($flights as $flightToStopDestination){
                foreach ($flightsToDestination[$airport] as $flightFromStopToDestination){
                    // STEP 4
                    if ($this->validateStopFlightTimes($flightToStopDestination, $flightFromStopToDestination)) {
                        $trips[$airport][] = $flightToStopDestination;
                        $trips[$airport][] = $flightFromStopToDestination;
                    }
                }
            }
        }
//        dd($trips);

        // STEP 5 - merge $directFlights to the $trips array
        $directTrip = [];
        $directTrip[$originAirport] = $directFlights;
        $trips = $directTrip + $trips;

        // STEP 6 - format trip
        $tripsResult = [];
        $tripsResponse = [];
        foreach ($trips as $airport => $flights){

            $flightsArray = [];
            foreach ($flights as $flight){
                $flightsArray[] = Flight::make((array) $flight);
            }

            $tripsResponse[] = $this->getFlightsResponse($flightsArray);
        }

//        dd($tripsResponse);

        return $tripsResponse;
    }

    private function validateStopFlightTimes($originFlight, $destinationFlight)
    {
//        dump($originFlight['number'] . ' = ' . $destinationFlight['number']);
//        dump($originFlight['arrival_time'] . ' < '. $destinationFlight['departure_time']);
        if ($originFlight['arrival_time'] < $destinationFlight['departure_time']){
            return true;
        }
        return false;
    }

    private function setFlightsArrayByAirportCode(array $flights)
    {
        $array = [];
        foreach($flights as $flight){
            if (!isset($array[$flight->code])){
                $array[$flight->code] = [];
            }
            $array[$flight->code][] = (array) $flight;
        }
        return $array;
    }

    /**
     * @param string $destinationAirport
     * @param string $departureDate
     * @param array $directFlights
     * @return array
     */
    private function selectStopFlightsGoingToDestination(string $destinationAirport, string $departureDate, array $directFlights)
    {
        $directFlightIds = array_map(fn ($flight) => $flight['id'], $directFlights);
        $directFlightIdsImploded = implode(',', $directFlightIds);

        // selecting flights going to the on DESTINATION (e.g. Vancouver)
        $sql = "
            SELECT flights.*,
                departure_airport.code AS code
                FROM flights
                JOIN airports AS departure_airport on departure_airport.id = departure_airport_id
                -- select flights where `arrival_airport` is the destination (e.g Vancouver)
                -- regardless of the departure airport (e.g origin is Montreal but there can also be flights from Cornwall and Toronto)
                AND departure_airport.id IN (
                    -- select flights where `arrival_airport` is the requested destination
                    SELECT airports.id FROM airports
                    JOIN airports AS arrival_airport on arrival_airport.id = arrival_airport_id
                    AND arrival_airport.code = :arrival_airport_code
                )
                WHERE flights.departure_date = :departure_date
                -- filter out the direct flights
                AND flights.id NOT IN ({$directFlightIdsImploded})
        ";

        $flightsToDestination = DB::select(DB::raw($sql), [
            'arrival_airport_code' => $destinationAirport,
            'departure_date' => $departureDate,
        ]);

        return $this->setFlightsArrayByAirportCode($flightsToDestination);
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
    private function getPaginatedTrips($trips, $perPage)
    {
        $trips = new LengthAwarePaginator($trips, count($trips), $perPage);
        $trips->withPath(Paginator::resolveCurrentPath());
        return $trips;
    }


    /**
     * Get a collection of `TripResponse`
     *
     * @param Flight[] $flights
     * @return Collection
     */
    private function getFlightsResponse(array $flights)
    {
        // convert the flights returned from database to a Collection of `FlightResponse`
        $flightsResponseCollection = collect($flights)->map(function ($flight) {
            return FlightResponse::fromFlight($flight);
        });

        $totalPrice = 0;
        foreach ($flightsResponseCollection as $flight){
            $totalPrice += $flight->getPrice();
        }
        $tripResponse = new TripResponse();
        $tripResponse->setPrice($totalPrice)
            ->setFlights($flightsResponseCollection->toArray());

        // convert the collection of FlightResponse to a collection of `TripResponse`
//        $tripsResponse = $flightsResponseCollection->map(function ($flightResponse) {
//            $tripResponse->addPrice(floatval($flightResponse->getPrice()))
//                ->addFlight($flightResponse);
//            return $tripResponse;
//        });

        return collect($tripResponse);
    }





}
