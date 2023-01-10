<?php
declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FlightRequest;
use App\Services\FlightService;
use Illuminate\Http\Resources\Json\ResourceCollection;

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
     * @queryParam stops int Number of stops, can be 0(direct flights only), 1 or 2. Example: 0.
     *
     * @queryParam page_size int Size per page. Defaults to 10. Example: 20
     * @queryParam page int Page to view. Example: 1
     * @unauthenticated
     *
     * @param FlightRequest $request
     * @return ResourceCollection
     */
    public function searchFlights(FlightRequest $request)
    {
        return $this->service->getFlights($request->validated());
    }

}
