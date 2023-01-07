<?php

namespace App\Http\Controllers;

use App\Http\Requests\AirlineRequest;
use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use App\Repositories\AirlineRepository;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\ResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;
use PHP_CodeSniffer\Reports\Json;

class AirlineController extends Controller
{
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
     * @param Request $request
     * @param AirlineRepository $repository
     * @return AirlineResource
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
     * Display the specified resource.
     *
     * @param  int  $id
     * @return AirlineResource
     */
    public function show(int $id)
    {
        return new AirlineResource(Airline::find($id));
    }

    /**
     * Update the specified resource in storage.
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
            abort(404);
        }

        $updated = $repository->update($airline, $request->only([
            'name',
            'code',
        ]));

        return new AirlineResource($updated);
    }

    /**
     * Delete the specified resource from storage.
     *
     * @param AirlineRepository $repository
     * @param int $id
     * @return Response
     */
    public function destroy(AirlineRepository $repository, int $id)
    {
        $airline = Airline::find($id);
        if (!$airline){
            abort(204);
        }
        $repository->delete($airline);

        return response(true, 204);
    }
}
