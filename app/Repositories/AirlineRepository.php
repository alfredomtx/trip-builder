<?php

namespace App\Repositories;

use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AirlineRepository
{
    public function insert(array $attributes)
    {
        return DB::transaction(function () use ($attributes){
            $created = Airline::create([
                'name' => data_get($attributes, 'name'),
                'code' => data_get($attributes, 'code'),
            ]);
            return $created;
        });

    }

    public function update(Airline $airline, array $attributes)
    {
        return DB::transaction(function () use ($airline, $attributes){
            $updated = $airline->update([
                'name' => data_get($attributes, 'name'),
                'code' => data_get($attributes, 'code'),
            ]);

            if (!$updated){
                throw new \Exception('Failed to update airline');
            }

            return $updated;
        });
    }

    public function delete(Airline $airline)
    {
        return DB::transaction(function () use ($airline){
            $deleted = $airline->delete();

            if (!$deleted){
                throw new \Exception('Failed to delete airline');
            }
        });
    }

}
