<?php

namespace App\Http\Controllers;

use App\Http\Requests\AirlineRequest;
use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use App\Repositories\AirlineRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Validation\Rule;
use PHP_CodeSniffer\Reports\Json;

class AirlineController extends Controller
{
    /**
     * Display a listing of the Airlines.
     *
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
     * Store a newly created Airline in storage.
     *
     * @param Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request, AirlineRepository $repository)
    {
        $request->validate([
            'name' => ['required', 'min:2'],
            'code' => ['required', 'min:2', 'unique:airlines,code'],
        ]);

        $created = $repository->insert($request->only([
            'name',
            'code',
        ]));

        return new AirlineResource($created);
    }

    /**
     * Display the specified Airline.
     *
     * @param  int  $id
     * @return AirlineResource
     */
    public function show(int $id)
    {
        return new AirlineResource(Airline::find($id));
    }

    /**
     * Update the specified Airline in storage.
     *
     * @param Request $request
     * @param AirlineRepository $repository
     * @param int $id
     * @return AirlineResource
     */
    public function update(Request $request, AirlineRepository $repository, int $id)
    {
        $request->validate([
            'name' => ['nullable', 'min:2'],
            'code' => ['nullable', 'min:2', 'unique:airlines,code'],
        ]);

        $airline = Airline::find($id);
        if (!$airline){
            abort(204);
        }

        $updated = $repository->update($airline, $request->only([
            'name',
            'code',
        ]));

        return new AirlineResource($updated);
    }

    /**
     * Remove the specified Airline from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(AirlineRepository $repository, int $id)
    {
        $airline = Airline::find($id);
        if (!$airline){
            abort(204);
        }
        $repository->delete($airline);

        return new JsonResponse(true, 204);
    }
}
