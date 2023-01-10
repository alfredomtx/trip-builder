<?php
namespace App\Services;

use App\Enums\StopsNumber;
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
        $filters = $this->setDefaultFilterValues($filters);

        $trips = $this->searchFlights($filters);
//        dd($trips);

        $paginatedTrips = $this->paginate($trips, $filters['page_size'], $filters['page']);
        return TripResource::collection($paginatedTrips);
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

    /**
     * @param array $filters
     * @return Collection[]
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

    /**
     * @param string $origin
     * @param string $destination
     * @param string $departureDate
     * @param StopsNumber $stops
     * @return array
     */
    private function getOneWayTrips(string $origin, string $destination, string $departureDate, bool $includeDirectflights)
    {
        $directFlights = $this->getDirectFlights($origin, $destination, $departureDate);
//        dd($directFlights);

        // STEP 1
        $flightsToDestination = $this->selectStopFlightsGoingToDestination($destination, $departureDate, $directFlights);
//        dd($flightsToDestination);

        // STEP 2
        $flightsToStopDestinations = $this->getFlightsToStopDestinations($origin, $departureDate, $flightsToDestination);

        // STEP 3 & STEP 4
        $trips = $this->buildTripsFromOriginToStopDestinations($flightsToStopDestinations, $flightsToDestination);
//        dd($trips);

        // STEP 5 - merge
        // only merge the direct flights if the stops is none
        // TODO: work on OneStop and TwoStops filter

        if ($includeDirectflights){
            $trips = $this->mergeDirectFlightsToTrips($origin, $directFlights, $trips);
        }

        return $trips;

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
        $stops = StopsNumber::from($filters['stops'] ?? '');

        if ($stops === StopsNumber::NoStops){
            return $this->getTripsWithDirectFlightsOnly($origin, $destination, $departureDate);
        }

        $trips = $this->getOneWayTrips($origin, $destination, $departureDate, true);
//        dd($trips);

        $tripsResponse = $this->createTripsResponseFromTrips($trips);
//        dd($tripsResponse);
        return $tripsResponse;
    }


    private function roundTrip(array $filters)
    {
        $origin = $filters['departure_airport'];
        $destination = $filters['arrival_airport'];
        $departureDate = $filters['departure_date'];
        $returnDate = $filters['return_date'];
        $stops = StopsNumber::from($filters['stops'] ?? '');

        $roundTrip = [];
        $roundTrip['departure'] = [];
        $roundTrip['departure']['directFlights'] = $this->getDirectFlights($origin, $destination, $departureDate);
        $roundTrip['return'] = [];
        $roundTrip['return']['directFlights'] = $this->getDirectFlights($destination, $origin, $returnDate);
//        dd($roundTrip);

        $roundTrip['directFlightTrips'] = $this->getRoundTripsWithDirectFlightsOnly($roundTrip['departure']['directFlights'],  $roundTrip['return']['directFlights']);
        if ($stops === StopsNumber::NoStops){
            return $roundTrip['directFlightTrips'];
        }

        unset($roundTrip['departure']['directFlights']);
        unset($roundTrip['return']['directFlights']);
        $roundTrip['departure']['trips'] = $this->getOneWayTrips($origin, $destination, $departureDate, false);
        $roundTrip['return']['trips'] = $this->getOneWayTrips($destination, $origin, $returnDate, false);

        $tripsResponse = $this->getRoundTripsWithStopFlights($roundTrip['departure']['trips'],  $roundTrip['return']['trips']);
        $tripsResponse = array_merge($roundTrip['directFlightTrips'], $tripsResponse);
//        dd($tripsResponse);
        return $tripsResponse;
    }

    /**
     * @param array $departureDirectFlights
     * @param array $returnDirectFlights
     * @return Collection[]
     */
    private function getRoundTripsWithStopFlights(array $departureTrips, array $returnTrips)
    {
        $trips = [];
        foreach ($departureTrips as $departureAirport => $departureTrip){
            $trips[$departureAirport] = [];
//            dump($departureAirport);
//            dump($departureTrip);
            foreach ($returnTrips as $returnAirport => $returnTrip){
//                dump($returnAirport);
//                dump($returnTrip);
                $trips[$departureAirport] = array_merge($departureTrip,  $returnTrip);
//                $trip[$departureAirport][] = $returnTrip;
//                dd($trips);

//                dd($trips);
            }
        }

        $tripsResponse = $this->createTripsResponseFromTrips($trips);


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
     * @param string $origin
     * @param string $destination
     * @param string $departureDate
     * @return Collection[]
     */
    private function getTripsWithDirectFlightsOnly(string $origin, string $destination, string $departureDate)
    {
        $directFlights = $this->getDirectFlights($origin, $destination, $departureDate);

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
    private function createTripsResponseFromTrips(array $trips)
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
    }

    /**
     * `$flightsToDestination` are flights to the destination (e.g. Vancouver) that are NOT from the
     * origin (e.g. Montreal)
     * With these flights, this function will search all flights from the origin (e.g. Montreal) to these
     * stop destinations (e.g. Cornwall, Toronto)
     *
     *
     * @param string $origin
     * @param string $departureDate
     * @param array $flightsToDestination
     * @return array
     */
    private function getFlightsToStopDestinations(string $origin, string $departureDate, array $flightsToDestination)
    {
        $flightsToStopDestinations = [];
        foreach ($flightsToDestination as $airport => $flights){
            if (!isset($flightsToStopDestinations[$airport])){
                $flightsToStopDestinations[$airport] = [];
            }
            $flightsToStopDestinations[$airport] = $this->getDirectFlights($origin, $airport, $departureDate);
        }
        return $flightsToStopDestinations;
    }

    /**
     * @param $origin
     * @param $destination
     * @param $departureDate
     * @return array
     */
    private function getDirectFlights($origin, $destination, $departureDate)
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
            'departure_airport_code' => $origin,
            'arrival_airport_code' => $destination,
            'departure_date' => $departureDate,
        ]);

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
        $filters['airline'] = $filters['airline'] ?? null;

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
     * @param string $destination
     * @param string $departureDate
     * @param array $directFlights
     * @return array
     */
    private function selectStopFlightsGoingToDestination(string $destination, string $departureDate, array $directFlights)
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
            'arrival_airport_code' => $destination,
            'departure_date' => $departureDate,
        ]);

        // convert all `StdClass` results to array
        $array = array_map(fn ($flight) => (array) $flight, $flightsToDestination);
        return $this->setFlightsArrayByAirportCode($array);
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





}
