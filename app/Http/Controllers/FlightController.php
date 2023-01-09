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

/**
 * @group Flight
 *
 * API to search for flights.
 */
class FlightController extends Controller
{

    protected FlightService $service;

    public function __construct(FlightService $service)
    {
        $this->service = $service;
    }
    /**
     * Search flights
     *
     * Search for flights according to search criteria and return paginated result.
     *
     * @queryParam departure_airport string required The departure airport `code`. Example: YUL.
     * @queryParam arrival_airport string required The arrival airport `code`. Example: YVR.
     * @queryParam departure_date date required Date of departure. Format `YYYY-MM-DD`
     * @queryParam trip_type string required Can be a `one-way` or `round-trip`.
     * @queryParam return_date date Date of return, required if `trip-type` is `round-trip`. Format `YYYY-MM-DD`
     *
     * @queryParam page_size int Size per page. Defaults to 10. Example: 20
     * @queryParam page int Page to view. Example: 1
     * @unauthenticated
     *
     * @param FlightRequest $request
     * @return array
     */
    public function searchFlights(FlightRequest $request)
    {
        return $this->service->searchFlights($request->validated());
    }

}
