<?php
namespace App\Services;

use App\Enums\StopsNumber;
use App\Enums\TripType;
use App\Exceptions\GeneralJsonException;
use App\Http\Resources\TripResource;
use App\Http\Responses\FlightResponse;
use App\Http\Responses\TripResponse;
use App\Models\Flight;
use App\Models\FlightSearch;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FlightService
{
    /**
     * @param array $filters
     * @return ResourceCollection
     * @throws GeneralJsonException
     */
    public function searchFlights(array $filters)
    {
        $filters = $this->setDefaultFilterValues($filters);

        $trips = match (TripType::from($filters['type'])) {
            TripType::OneWay => $this->oneWayTrip($filters),
            TripType::RoundTrip => $this->roundTrip($filters),
            default => throw new GeneralJsonException('Invalid `trip_type`.', 422),
        };

        $paginatedTrips = $this->paginate($trips, $filters['page_size'], $filters['page']);
        return TripResource::collection($paginatedTrips);
    }

    /**
     * @param array $filters
     * @return Collection
     */
    private function oneWayTrip(array $filters)
    {
        $origin = $filters['departure_airport'];
        $destination = $filters['arrival_airport'];
        $departureDate = $filters['departure_date'];
        $airline = $filters['airline'];
        $stops = StopsNumber::from($filters['stops'] ?? '');

        $flightSearch = new FlightSearch();
        $flightSearch->setDepartureDate($departureDate)
            ->setDestination($destination)
            ->setOrigin($origin)
            ->setAirlineCode($airline);

        if ($stops === StopsNumber::NoStops){
            return $this->getTripsWithDirectFlightsOnly($flightSearch);
        }

        $includeDirectFlights = match($stops){
            StopsNumber::OneOrMore => false,
            default => true,
        };
        $trips = $this->getOneWayTrips($flightSearch, $includeDirectFlights);

        $tripsResponse = $this->getTripsResponseFromTrips($trips);
        return $tripsResponse;
    }

    private function roundTrip(array $filters)
    {
        $origin = $filters['departure_airport'];
        $destination = $filters['arrival_airport'];
        $departureDate = $filters['departure_date'];
        $returnDate = $filters['return_date'];
        $airline = $filters['airline'];
        $stops = StopsNumber::from($filters['stops'] ?? '');

        $flightSearch = new FlightSearch();
        $flightSearch->setDepartureDate($departureDate)
            ->setDestination($destination)
            ->setOrigin($origin)
            ->setAirlineCode($airline);

        $roundTrip = [];
        $roundTrip['departure'] = [];
        $roundTrip['departure']['directFlights'] = $this->selectDirectFlights($flightSearch);

        // invert destination and origin
        $flightSearch->setDepartureDate($returnDate)
            ->setDestination($origin)
            ->setOrigin($destination);

        $roundTrip['return'] = [];
        $roundTrip['return']['directFlights'] = $this->selectDirectFlights($flightSearch);
//        dd($roundTrip);

        $roundTrip['directFlightTrips'] = $this->getRoundTripsWithDirectFlightsOnly($roundTrip['departure']['directFlights'],  $roundTrip['return']['directFlights']);
        if ($stops === StopsNumber::NoStops){
            return $roundTrip['directFlightTrips'];
        }

        $flightSearch->setDepartureDate($departureDate)
            ->setDestination($destination)
            ->setOrigin($origin);

        $roundTrip['departure']['trips'] = $this->getOneWayTrips($flightSearch, false);

        // invert destination and origin
        $flightSearch->setDepartureDate($returnDate)
                ->setDestination($origin)
                ->setOrigin($destination);

        $roundTrip['return']['trips'] = $this->getOneWayTrips($flightSearch,false);

        $tripsResponse = $this->getRoundTripsWithStopFlights($roundTrip['departure']['trips'],  $roundTrip['return']['trips']);

        if ($stops != StopsNumber::OneOrMore){
            $tripsResponse = array_merge($roundTrip['directFlightTrips'], $tripsResponse);
        }
        return $tripsResponse;
    }

    /**
     * @param FlightSearch $flightSearch
     * @param bool $includeDirectFlights
     * @return array $trips
     */
    private function getOneWayTrips(FlightSearch $flightSearch, bool $includeDirectFlights)
    {
        $directFlights = $this->getDirectFlights($flightSearch);

        // STEP 1
        $flightsToDestination = $this->selectStopFlightsGoingToDestination($flightSearch, $directFlights);
//        dd($flightsToDestination);
        if (empty($flightsToDestination)){
            // return trips with direct flights
            if ($includeDirectFlights){
                $trips = $this->setFlightsArrayByAirportCode($directFlights);
                return $trips;
            }
            return [];
        }

        // STEP 2
        $flightsToStopDestinations = $this->getFlightsToStopDestinations($flightSearch, $flightsToDestination);
//        dd($flightsToStopDestinations);

        // STEP 3 & STEP 4
        $trips = $this->buildTripsFromOriginToStopDestinations($flightsToStopDestinations, $flightsToDestination);
//        dd($trips);
        // STEP 5 - merge
        if ($includeDirectFlights){
            $trips = $this->mergeDirectFlightsToTrips($flightSearch->origin, $directFlights, $trips);
        }
        return $trips;

    }

    private function getDirectFlights(FlightSearch $flightSearch)
    {
        $directFlights = $this->selectDirectFlights($flightSearch);
        $directFlights = $this->filterByAirline($flightSearch->airlineCode, $directFlights);
        if (empty($directFlights)){
            return [];
        }
        return $directFlights;
    }

    /**
     * @param string $airlineCode
     * @param array $flights
     * @return array
     */
    private function filterByAirline(string $airlineCode, array $flights)
    {
        if (empty($airlineCode)){
            return $flights;
        }
        $flights = array_filter($flights, function ($flight) use ($airlineCode) {
            if ($flight['airline_code'] != $airlineCode){
                return false;
            }
            return true;
        });
        return $flights;
    }

    /**
     * @param array $departureTrips
     * @param array $returnTrips
     * @return Collection
     */
    private function getRoundTripsWithStopFlights(array $departureTrips, array $returnTrips)
    {
        $trips = [];
        foreach ($departureTrips as $departureAirport => $departureTrip){
            $trips[$departureAirport] = [];
            foreach ($returnTrips as $returnAirport => $returnTrip){
                $trips[$departureAirport] = array_merge($departureTrip,  $returnTrip);
            }
        }
        $tripsResponse = $this->getTripsResponseFromTrips($trips);
        return $tripsResponse;
    }


        /**
     * @param array $departureDirectFlights
     * @param array $returnDirectFlights
     * @return Collection[]
     */
    private function getRoundTripsWithDirectFlightsOnly(array $departureDirectFlights, array $returnDirectFlights)
    {
        $trips = [];
        foreach ($departureDirectFlights as $departureFlight){
            foreach ($returnDirectFlights as $returnFlight){
                $flights = [];
                $flights[] =  Flight::make($departureFlight);
                $flights[] =  Flight::make($returnFlight);
                $trips[] = $this->getTripResponseFromFlights($flights);
            }
        }
        return $trips;
    }

    /**
     * @param FlightSearch $flightSearch
     * @return Collection
     */
    private function getTripsWithDirectFlightsOnly(FlightSearch $flightSearch)
    {
        $directFlights = $this->selectDirectFlights($flightSearch);

        $tripsResponse = $this->getTripsResponseFromDirectFlights($directFlights);
        return $tripsResponse;
    }

    private function getTripsResponseFromDirectFlights(array $directFlights)
    {
        // convert each flight to a TripResponse and return as array
        $tripsResponse = [];
        foreach($directFlights as $flight){
            $flight = Flight::make($flight);
            $tripsResponse[] = $this->getTripResponseFromFlights(array($flight));
        }
        return $tripsResponse;
    }


    /**
     * Create an array of `TripResponse` from the trips of the algorithm.
     *
     * @param array $trips
     * @return Collection[]
     */
    private function getTripsResponseFromTrips(array $trips)
    {
        $tripsResponse = [];
        foreach ($trips as $airport => $flights){
            $flightsArray = [];
            foreach ($flights as $flight){
                $flightsArray[] = Flight::make((array) $flight);
            }
            $tripsResponse[] = $this->getTripResponseFromFlights($flightsArray);
        }
        return $tripsResponse;
    }

    private function createTripFromFlights(string $airportCode, array $flights)
    {
        $trip = [];
        $trip[$airportCode] = [];
        foreach ($flights as $flight){
            $trip[$airportCode][] = $flight;
        }

        return $trip;
    }

    /**
     * Merge $directFlights to the $trips array
     *
     * @param string $origin
     * @param array $directFlights
     * @param array $trips
     * @return array
     */
    private function mergeDirectFlightsToTrips(string $origin, array $directFlights, array $trips)
    {
        if (empty($directFlights)){
            return $trips;
        }
        $directTrip = [];
        $directTrip[$origin] = $directFlights;
        $trips = $directTrip + $trips;
        return $trips;
    }

    /**
     * Build the `Trips` from the flights to stop destinations, and validate the flight times
     * to filter out invalid flights (i.e. flights where the arrival time from origin to stop destination are earlier
     * than the departure time of the stop destination flight (a bit confusing, I know).
     *
     * @param array $flightsToStopDestinations
     * @param array $flightsToDestination
     * @return array
     */
    private function buildTripsFromOriginToStopDestinations(array $flightsToStopDestinations, array $flightsToDestination)
    {
//        dump($flightsToStopDestinations);
//        dump($flightsToDestination);
//        dd();
        $trips = [];
        foreach ($flightsToStopDestinations as $airport => $flights)
        {
            if (!isset($trips[$airport])){
                $trips[$airport] = [];
            }

            foreach ($flights as $flightToStopDestination){
                // compare flights from origin -> stop destination
                foreach ($flightsToDestination[$airport] as $flightFromStopToDestination){
                    // STEP 4
                    if ($this->validateStopFlightTimes($flightToStopDestination, $flightFromStopToDestination)) {
                        $trips[$airport][] = $flightToStopDestination;
                        $trips[$airport][] = $flightFromStopToDestination;
                    }
                }
            }
        }
        return $trips;

        // loop through the trips and check if there is any Airport without flights
        $airportsWithoutFlight = [];
        foreach ($trips as $airport => $flights){
            if (count($flights) == 0){
                $airportsWithoutFlight[] = $airport;
            }
        }
        // remove the airports without flight
        foreach ($airportsWithoutFlight as $airport){
            unset($trips[$airport]);
        }
        return $trips;
    }

    /**
     * `$flightsToDestination` are flights to the destination (e.g. Vancouver) that are NOT from the
     * origin (e.g. Montreal)
     * With these flights, this function will search all flights from the origin (e.g. Montreal) to these
     * stop destinations (e.g. Cornwall, Toronto)
     *
     *
     * @param FlightSearch $flightSearch
     * @param array $flightsToDestination
     * @return array
     */
    private function getFlightsToStopDestinations(FlightSearch $flightSearch, array $flightsToDestination)
    {
        $flightsToStopDestinations = [];
        foreach ($flightsToDestination as $airport => $flights){
            if (!isset($flightsToStopDestinations[$airport])){
                $flightsToStopDestinations[$airport] = [];
            }

            $flightSearch->setDestination($airport);
            $flightsToStopDestinations[$airport] = $this->selectDirectFlights($flightSearch);
        }
        return $flightsToStopDestinations;
    }

    /**
     * @param FlightSearch $flightSearch
     * @return array
     */
    private function selectDirectFlights(FlightSearch $flightSearch)
    {
        $airlineCondition = (empty($flightSearch->airlineCode)) ? '' : 'AND airlines.code = :airline_code';

        // get direct flights from origin to destination
        $sql = "
            SELECT flights.*,
                departure_airport.code AS code,
                airlines.code AS airline_code
                FROM flights

                JOIN airports AS departure_airport ON departure_airport.id = departure_airport_id
                AND departure_airport.code = :departure_airport_code
                JOIN airports AS arrival_airport ON arrival_airport.id = arrival_airport_id
                AND arrival_airport.code = :arrival_airport_code
                JOIN airlines ON airlines.id = flights.airline_id

                WHERE departure_date = :departure_date
                {$airlineCondition}
        ";

        $queryParams = [
            'departure_airport_code' => $flightSearch->origin,
            'arrival_airport_code' => $flightSearch->destination,
            'departure_date' => $flightSearch->departureDate,
        ];
        if (!empty($flightSearch->airlineCode)){
            $queryParams['airline_code'] = $flightSearch->airlineCode;
        }

        $directFlights = DB::select(DB::raw($sql), $queryParams);

        // convert all flights to array
        return array_map(fn ($flight) => (array) $flight, $directFlights);
    }

    /**
     * Set the default values in the $filter array and return it.
     *
     * @param array $filters
     * @return array
     */
    private function setDefaultFilterValues(array $filters)
    {
        $filters['page_size'] = $filters['page_size'] ?? 10;
        $filters['page'] = $filters['page'] ?? 1;
        $filters['stops'] = $filters['stops'] ?? '';
        $filters['airline'] = $filters['airline'] ?? '';

        return $filters;
    }

    /**
     * Validate if the `arrival_time` of the origin flight (e.g. Montreal) is earlier than the `departure_time`
     * of the stop destination(e.g. Toronto) to the destination (e.g. Vancouver).
     *
     * @param $originFlight
     * @param $destinationFlight
     * @return bool
     */
    private function validateStopFlightTimes($originFlight, $destinationFlight)
    {
        if ($originFlight['arrival_time'] < $destinationFlight['departure_time']){
            return true;
        }
//        dd($originFlight);
        return false;
    }

    /**
     * Format the array by airport code.
     * Example:
     *  [
     *      "YUL": [
     *          ...flight1,
     *          ...flight2,
     *      ],
     *      "YVR": [
     *          ...flight1,
     *          ...
     *      ],
     * ]
     *
     * @param array $flights
     * @return array
     */
    private function setFlightsArrayByAirportCode(array $flights)
    {
        $array = [];
        foreach($flights as $flight){
            if (!isset($array[$flight['code']])){
                $array[$flight['code']] = [];
            }
            $array[$flight['code']][] = (array) $flight;
        }
        return $array;
    }

    /**
     * @param FlightSearch $flightSearch
     * @param array $directFlights
     * @return array
     */
    private function selectStopFlightsGoingToDestination(FlightSearch $flightSearch, array $directFlights)
    {
        // create the WHERE condition statement to filter out the direct flights, if there are any
        $directFlightsWhereCondition = '';
        $directFlightIds = array_map(fn ($flight) => $flight['id'], $directFlights);
        if (count($directFlightIds) > 0){
            $directFlightIdsImploded = implode(',', $directFlightIds);
            $directFlightsWhereCondition = "AND flights.id NOT IN ({$directFlightIdsImploded})";
        }

        $airlineCondition = (empty($flightSearch->airlineCode)) ? '' : 'AND airlines.code = :airline_code';

        // selecting flights going to the on DESTINATION (e.g. Vancouver)
        $sql = "
            SELECT flights.*,
                departure_airport.code AS code,
                airlines.code AS airline_code
                FROM flights
                JOIN airlines ON airlines.id = flights.airline_id
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
                {$directFlightsWhereCondition}
                {$airlineCondition}

        ";

        $queryParams = [
            'arrival_airport_code' => $flightSearch->destination,
            'departure_date' => $flightSearch->departureDate,
        ];
        if (!empty($flightSearch->airlineCode)){
            $queryParams['airline_code'] = $flightSearch->airlineCode;
        }

        $flightsToDestination = DB::select(DB::raw($sql), $queryParams);

        // convert all `StdClass` results to array
        $flights = array_map(fn ($flight) => (array) $flight, $flightsToDestination);
        $flights = $this->filterByAirline($flightSearch->airlineCode, $flights);
        return $this->setFlightsArrayByAirportCode($flights);
    }

    private function paginateQuery($query, int $pageSize, int $page)
    {
        return $query->paginate($pageSize, ['*'], 'page', $page);
    }

    /**
     * Get a collection of `TripResponse`
     *
     * @param Flight[] $flights
     * @return Collection
     */
    private function getTripResponseFromFlights(array $flights)
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

        return collect($tripResponse);
    }

    /**
     * @param array $items
     * @param int $perPage
     * @param int|null $page
     * @return LengthAwarePaginator
     */
    public function paginate(array $items, int $perPage = 4, int $page = null)
    {
        $page = $page ?: (Paginator::resolveCurrentPage() ?: 1);
        $total = count($items);
        $currentPage = $page;
        $offset = ($currentPage * $perPage) - $perPage ;
        $itemSToShow = array_slice($items, $offset, $perPage);

        $paginator = new LengthAwarePaginator($itemSToShow, $total, $perPage);
        $paginator->withPath(Paginator::resolveCurrentPath());
        return $paginator;
    }




}
