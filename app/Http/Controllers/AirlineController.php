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

class AirlineController extends Controller
{
    protected AirlineService $service;

    public function __construct(AirlineService $service)
    {
        $this->service = $service;
    }

    /**
     * Display a listing of the resource.
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
     * Store a newly created resource in storage.
     *
     * @param AirlineRequest $request
     * @param AirlineRepository $repository
     * @return AirlineResource
     */
    public function store(AirlineRequest $request, AirlineRepository $repository)
    {
        $request->validated();

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
     * @param AirlineRequest $request
     * @param int $id
     * @return AirlineResource
     * @throws GeneralJsonException
     */
    public function update(AirlineRequest $request, int $id)
    {
        $request->validated();

        $resource = $this->service->update($id, $request->only([
            'name',
            'code',
        ]));
        return new AirlineResource($resource);
    }

    /**
     * Delete the specified resource from storage.
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
