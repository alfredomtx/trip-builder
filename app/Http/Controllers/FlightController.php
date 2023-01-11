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
     * Briefly, flights can be searched by departure airport, arrival airport, departure date and return date(if is a round trip).
     *
     *
     * @unauthenticated
     *
     * @param FlightRequest $request
     * @return ResourceCollection
     */
    public function searchFlights(FlightRequest $request)
    {
        return $this->service->searchFlights($request->validated());
    }

}
