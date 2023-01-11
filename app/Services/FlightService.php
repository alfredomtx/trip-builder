<?php
namespace App\Services;

use App\Enums\SortBy;
use App\Enums\SortOrder;
use App\Enums\StopsNumber;
use App\Enums\TripType;
use App\Http\Resources\TripResource;
use App\Http\Responses\FlightResponse;
use App\Http\Responses\TripResponse;
use App\Models\Flight;
use App\Models\FlightSearch;
use App\Models\Trip;
use App\Models\TripsBuilder;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class FlightService
{
    /**
     * Return the flights in a Paginated response according to the search criteria and filters.
     * @param array $filters
     * @return ResourceCollection
     */
    public function searchFlights(array $filters)
    {
        $filters = $this->setDefaultFilterValues($filters);

        $tripsResponse = match (TripType::from($filters['type'])) {
            TripType::OneWay => $this->oneWayTrip($filters),
            TripType::RoundTrip => $this->roundTrip($filters),
        };

        $tripsResponse = $this->sortTripsResponse($tripsResponse, $filters);

        $paginatedTrips = $this->paginate($tripsResponse, $filters['page_size'], $filters['page']);
        return TripResource::collection($paginatedTrips);
    }

    private function sortTripsResponse(array $tripsResponse, array $filters)
    {
        switch (SortBy::tryFrom($filters['sort_by'])){
            case SortBy::Price:
                $keys = array_column($tripsResponse, SortBy::Price->value);
                match(SortOrder::from($filters['sort_order'])){
                    SortOrder::Asc => array_multisort($keys, SORT_ASC, $tripsResponse),
                    SortOrder::Desc => array_multisort($keys, SORT_DESC, $tripsResponse),
                };
                break;
        }
        return $tripsResponse;
    }

    /**
     * Search for flights using the `one-way` criteria (i.e. trip without a return trip).
     *
     * @param array $filters
     * @return TripResponse[]
     */
    private function oneWayTrip(array $filters)
    {
        $stops = StopsNumber::from($filters['stops'] ?? '');

        $flightSearch = new FlightSearch();
        $flightSearch->setDepartureDate($filters['departure_date'])
            ->setDestination($filters['arrival_airport'])
            ->setOrigin($filters['departure_airport'])
            ->setAirlineCode($filters['airline']);

        if ($stops === StopsNumber::NoStops){
            $directFlights = $this->selectDirectFlights($flightSearch);
            $tripsResponse = $this->getTripsResponseFromDirectFlights($directFlights);
            return $tripsResponse;
        }

        $includeDirectFlights = match($stops){
            StopsNumber::OneOrMore => false,
            default => true,
        };
        $trips = $this->getOneWayTrips($flightSearch, $includeDirectFlights);

        $tripsResponse = $this->getTripsResponseFromTrips($trips);
        return $tripsResponse;
    }

    /**
     * Search for flights using the `round-trip` criteria (i.e. trip with other return trips).
     *
     * @param array $filters
     * @return Collection[] TripResponse
     */
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

        // get direct flights from origin to destination at departure date
        $roundTrip = [];
        $roundTrip['departure'] = [];
        $roundTrip['departure']['directFlights'] = $this->selectDirectFlights($flightSearch);

        // invert destination and origin
        $flightSearch->setDepartureDate($returnDate)
            ->setDestination($origin)
            ->setOrigin($destination);

        // get direct flights from destination to origin at return date
        $roundTrip['return'] = [];
        $roundTrip['return']['directFlights'] = $this->selectDirectFlights($flightSearch);

        // convert direct flights to Trips
        $roundTrip['directFlightTrips'] = $this->getRoundTripsWithDirectFlightsOnly($roundTrip['departure']['directFlights'],  $roundTrip['return']['directFlights']);
        if ($stops === StopsNumber::NoStops){
            $tripsResponse = $this->getTripsResponseFromTrips($roundTrip['directFlightTrips']);
            return $tripsResponse;
        }

        $flightSearch->setDepartureDate($departureDate)
            ->setDestination($destination)
            ->setOrigin($origin);

        // get all trips from origin to destination at departure date, including stop flights
        $roundTrip['departure']['trips'] = $this->getOneWayTrips($flightSearch, false);

        // invert destination and origin
        $flightSearch->setDepartureDate($returnDate)
                ->setDestination($origin)
                ->setOrigin($destination);

        // get all trips from destination to origin at return date, including stop flights
        $roundTrip['return']['trips'] = $this->getOneWayTrips($flightSearch,false);

        // convert flights to Trips
        $trips = $this->getRoundTripsWithStopFlights($roundTrip['departure']['trips'], $roundTrip['return']['trips']);
        $tripsResponse = $this->getTripsResponseFromTrips($trips);

        // if `stops` is not 1, add the direct flight trips to the response
        if ($stops != StopsNumber::OneOrMore){
            $tripsResponse = array_merge($this->getTripsResponseFromTrips($roundTrip['directFlightTrips']), $tripsResponse);
        }
        return $tripsResponse;
    }

    /**
     * Get all Trips according to FlightSearch object criteria, including stop flights.
     *
     * @param FlightSearch $flightSearch
     * @param bool $includeDirectFlights
     * @return Trip[] array of `Trip` object
     */
    private function getOneWayTrips(FlightSearch $flightSearch, bool $includeDirectFlights)
    {
        // instantiate a TripsBuilder object to help organizing data and build an array of `Trip` objects at the end.
        $tripsBuilder = new TripsBuilder();

        $directFlights = $this->getDirectFlights($flightSearch);
        $tripsBuilder->directFlights = $this->getTripsByFlights($directFlights);

        // STEP 1
        $toDestinationFlights = $this->selectStopFlightsGoingToDestination($flightSearch, $directFlights);
        $tripsBuilder->toDestination = $this->getTripsByFlights($toDestinationFlights);
        if (empty($tripsBuilder->toDestination)){
            // return trips with direct flights
            if ($includeDirectFlights){
                return $tripsBuilder->directFlights;
            }
            return [];
        }

        // STEP 2
        $tripsBuilder->toStopDestinations = $this->getFlightsFromOriginToStopDestinations($flightSearch, $tripsBuilder);

        // STEP 3 & STEP 4
        $trips = $this->buildTripsFromOriginToStopDestinations($tripsBuilder);

        // STEP 5 - merge
        if ($includeDirectFlights){
            $trips = array_merge($tripsBuilder->directFlights, $trips);
        }

        return $trips;
    }

    /**
     * Get the direct flights from the origin to destination.
     *
     * @param FlightSearch $flightSearch
     * @return array array of `Flight` objects as array.
     */
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
     * Loop throgh every flight and filter out flights where the `airline code` does not match.
     *
     * @param string $airlineCode
     * @param array $flights array of `Flight` objects as array.
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
     * From the $departureTrips and $returnTrips arrays, it will return a new array of `Trip` objects
     * In the structure that is expected for a `round-trip` flight (i.e. flights from departure and return date in the
     * same trip).
     *
     * @param array $departureTrips
     * @param array $returnTrips
     * @return Trip[] array of `Trip` objects.
     */
    private function getRoundTripsWithStopFlights(array $departureTrips, array $returnTrips)
    {
        $trips = [];
        foreach ($departureTrips as $departureTrip){
            $trip = new Trip();
            foreach ($returnTrips as $returnTrip){
                $trip->setFlights($departureTrip->getFlights());
                $trip->addFlights($returnTrip->getFlights());
            }
            $trips[] = $trip;
        }
        return $trips;
    }

    /**
     * From the $departureTrips and $returnTrips arrays, it will return a new array of `Trip` objects
     * In the structure that is expected for a `round-trip` flight (i.e. flights from departure and return date in the
     * same trip).
     *
     * @param array $departureDirectFlights
     * @param array $returnDirectFlights
     * @return Trip[] array of `Trip` objects.
     */
    private function getRoundTripsWithDirectFlightsOnly(array $departureDirectFlights, array $returnDirectFlights)
    {
        $trips = [];
        foreach ($departureDirectFlights as $departureFlight){
            foreach ($returnDirectFlights as $returnFlight){
                $trip = new Trip();
                $trip->addFlight($departureFlight);
                $trip->addFlight($returnFlight);
                $trips[] = $trip;
            }
        }
        return $trips;
    }

    /**
     * When searching for `one-way` trips and with `stops` = 0 (direct flights only),
     * We build the TripResponse directly from Flights instead of from a `Trip` object that contains the flights.
     * This is because in this scenario, each trip will always have only 1 flight.
     *
     * @param array $directFlights
     * @return TripResponse[]
     */
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
     * Create an array of `TripResponse` from an array of `Trip` objects.
     *
     * @param Trip[] $trips
     * @return TripResponse[]
     */
    private function getTripsResponseFromTrips(array $trips)
    {
        $tripsResponse = [];
        foreach ($trips as $trip){
            $flightsArray = [];
            foreach ($trip->getFlights() as $flight){
                $flightsArray[] = Flight::make((array) $flight);
            }
            $tripsResponse[] = $this->getTripResponseFromFlights($flightsArray);
        }
        return $tripsResponse;
    }

    /**
     * Build the `Trips` from the flights to stop destinations, and validate the flight times
     * to filter out invalid flights.
     * Flights where the arrival time from origin to stop destination are earlier
     * than the departure time of the stop destination flight (a bit confusing, I know).
     *
     * @param TripsBuilder $tripsBuilder
     * @return Trip[] array of `Trip` objects.
     */
    private function buildTripsFromOriginToStopDestinations(TripsBuilder $tripsBuilder)
    {
        // since the $tripsBuilder->toDestination has an array of `Trip` objects,
        // we need to create this array by airport to loop through it below
        $flightsToDestinationByAirport = [];
        foreach ($tripsBuilder->toDestination as $trip){
            $flightsToDestinationByAirport[$trip->getAirportCode()] = $trip->getFlights();
        }

        $trips = [];
        $trip = null;
        foreach ($tripsBuilder->toStopDestinations as $airport => $flights)
        {
            // first iteration
            if ($trip === null){
                $trip = new Trip();
                $trip->setAirportCode($airport);
            } else if ($trip->getAirportCode() != $airport){
                $trip = new Trip();
                $trip->setAirportCode($airport);
            }

            foreach ($flights as $flightToStopDestination){
                // compare flights from origin -> stop destination
                foreach ($flightsToDestinationByAirport[$airport] as $flightFromStopToDestination){
                    // STEP 4
                    if ($this->validateStopFlightTimes($flightToStopDestination, $flightFromStopToDestination)) {
                        $trip->addFlight($flightToStopDestination);
                        $trip->addFlight($flightFromStopToDestination);
                        $trips[] = $trip;
                    }
                }
            }
        }

        // loop through the trips and check if there is any `Trip` without flights
        $newTrips = [];
        foreach ($trips as $trip){
            if (count($trip->getFlights()) == 0){
                continue;
            }
            $newTrips[] = $trip;
        }
        return $newTrips;
    }

    /**
     * `$toDestination` are flights to the destination (e.g. Vancouver) that are NOT from the origin (e.g. Montreal).
     * With these flights, this function will search all flights from the origin (e.g. Montreal) to these
     * stop destinations (e.g. Cornwall, Toronto).
     *
     * @param FlightSearch $flightSearch
     * @param TripsBuilder $tripsBuilder
     * @return array array of `Flight` object as array.
     */
    private function getFlightsFromOriginToStopDestinations(FlightSearch $flightSearch, TripsBuilder $tripsBuilder)
    {
        $toStopDestinations = [];
        foreach ($tripsBuilder->toDestination as $trip){
            if (!isset($toStopDestinations[$trip->getAirportCode()])){
                $toStopDestinations[$trip->getAirportCode()] = [];
            }

            $flightSearch->setDestination($trip->getAirportCode());
            $toStopDestinations[$trip->getAirportCode()] = $this->selectDirectFlights($flightSearch);
        }
        return $toStopDestinations;
    }

    /**
     * Select in the database direct flights from origin to destination.
     *
     * @param FlightSearch $flightSearch
     * @return array array of `Flight` objects as array.
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
        $filters['sort_by'] = $filters['sort_by'] ?? '';
        $filters['sort_order'] = $filters['sort_order'] ?? '';

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
        return false;
    }

    /**
     * Create an array of `Trip` objects from the flights. The array is by `departure` airport code, so each entry is a `Trip`
     * with a different airport code.
     *
     * @param array $flights
     * @return Trip[] array of `Trip` object
     */
    private function getTripsByFlights(array $flights)
    {
//        dd($flights);
        $trip = null;
        $trips = [];
        foreach($flights as $flight){
            // first iteration
            if ($trip === null){
                $trip = new Trip();
                $trip->setAirportCode($flight['code']);
                $trip->addFlight($flight);

                // first airport, add a new trip
                $trips[] = $trip;
            } else if ($trip->getAirportCode() != $flight['code']){
                // create a new Trip instance for every airport
                $trip = new Trip();
                $trip->setAirportCode($flight['code']);
                $trip->addFlight($flight);

                // new airport, add a new trip
                $trips[] = $trip;

            } else {
                $trip->addFlight($flight);
            }
        }
        return $trips;
    }

    /**
     * Select flights going to the on DESTINATION (e.g. Vancouver) that are NOT direct flights
     * from the origin (e.g. Montreal).
     *
     * @param FlightSearch $flightSearch
     * @param array $directFlights
     * @return array array of `Flight` objects as array.
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

        $toDestination = DB::select(DB::raw($sql), $queryParams);

        // convert all `StdClass` results to array
        $flights = array_map(fn ($flight) => (array) $flight, $toDestination);
        $flights = $this->filterByAirline($flightSearch->airlineCode, $flights);
        return $flights;
    }

    /**
     * Get a collection of `TripResponse` from an array of `Flight` objects as array.
     *
     * @param Flight[] $flights
     * @return array TripResponse
     */
    private function getTripResponseFromFlights(array $flights)
    {
        // convert the flights returned from database to a Collection of `FlightResponse` as array
        $flightsResponseCollection = collect($flights)->map(function ($flight) {
            return FlightResponse::fromFlight($flight)->toArray();
        });


        $totalPrice = 0;
        foreach ($flightsResponseCollection as $flight){
            $totalPrice += $flight['price'];
        }
        $tripResponse = new TripResponse();
        $tripResponse->setPrice($totalPrice)
            ->setFlights($flightsResponseCollection->toArray());

        return $tripResponse->toArray();
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
