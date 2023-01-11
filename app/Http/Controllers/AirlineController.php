<?php

namespace App\Http\Controllers;

use App\Exceptions\GeneralJsonException;
use App\Http\Requests\AirlineRequest;
use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use App\Models\Airport;
use App\Repositories\AirlineRepository;
use App\Services\AirlineService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use PHP_CodeSniffer\Reports\Json;

/**
 * @group Airline
 *
 * APIs to manage the airline resource.
 *
 * This is the only resource available currently as a REST endpoint.
 * It means there are endpoints for every operation: create, delete, update, search.
 *
 * Ideally, there should be also REST endpoints for other resources, such as Flights, Cities and Airports.
 */
class AirlineController extends Controller
{
    protected AirlineService $service;

    public function __construct(AirlineService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resources
     *
     * Get a list of airlines.
     *
     * @param Request $request
     * @return ResourceCollection
     */
    public function index(Request $request)
    {
        $data = $request->all();

        $perPage = $data['per_page'] ?? 10;
        $airlines = Airline::query()
            ->latest()
            ->paginate($perPage);

        return AirlineResource::collection($airlines);
    }

    /**
     * Create new resource
     *
     * Store a newly created resource in storage.
     *
     *
     * @param AirlineRequest $request
     * @param AirlineRepository $repository
     * @return AirlineResource
     */
    public function store(AirlineRequest $request, AirlineRepository $repository)
    {
        $created = $this->service->insert($request->only([
            'name',
            'code',
        ]));
        return new AirlineResource($created);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return AirlineResource
     */
    public function show(int $id)
    {
        $resource = $this->service->get($id);
        return new AirlineResource($resource);
    }

    /**
     * Update the specified resource in storage.
     *
     * @bodyParam name string required Name of the airline. Example: Air Canada
     * @bodyParam code string required IATA Code of the airline. Example: AC
     *
     * @param AirlineRequest $request
     * @param int $id
     * @return AirlineResource
     * @throws GeneralJsonException
     */
    public function update(AirlineRequest $request, int $id)
    {
        $resource = $this->service->update($id, $request->only([
            'name',
            'code',
        ]));
        return new AirlineResource($resource);
    }

    /**
     * Delete the specified resource from storage.
     *
     * @response 204
     *
     * @param int $id
     * @return Response
     */
    public function destroy(int $id)
    {
        $this->service->delete($id);
        return response(null, 204);
    }
}
