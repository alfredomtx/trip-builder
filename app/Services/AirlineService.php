<?php

namespace App\Services;

use App\Exceptions\GeneralJsonException;
use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use App\Repositories\AirlineRepository;

class AirlineService
{
    protected AirlineRepository $repository;

    public function __construct(AirlineRepository $repository)
    {
        $this->repository = $repository;
    }

    /**
     * @param int $id
     * @return Airline
     * @throws GeneralJsonException
     */
    public function get(int $id)
    {
        $airline = Airline::find($id);
        if (!$airline){
            throw new GeneralJsonException('Resource not found.', 404);
        }

        return $airline;
    }

    /**
     * @param array $attributes
     * @return Airline
     */
    public function insert(array $attributes)
    {
        $created = $this->repository->insert($attributes);
        return $created;
    }

    /**
     * @param int $id
     * @param array $attributes
     * @return Airline
     * @throws GeneralJsonException
     */
    public function update(int $id, array $attributes)
    {
        $airline = Airline::find($id);
        if (!$airline){
            throw new GeneralJsonException('Resource not found.', 404);
        }

        $updated = $this->repository->update($airline, $attributes);
        return $updated;
    }

    /**
     * @param int $id
     * @return void
     */
    public function delete(int $id)
    {
        $airline = Airline::find($id);
        if (!$airline){
            abort(204);
        }

        $this->repository->delete($airline);
    }
}
