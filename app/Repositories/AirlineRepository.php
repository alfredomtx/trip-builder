<?php

namespace App\Repositories;

use App\Exceptions\GeneralJsonException;
use App\Models\Airline;
use Illuminate\Support\Facades\DB;

class AirlineRepository
{
    /**
     * @param array $attributes
     * @return Airline
     */
    public function insert(array $attributes)
    {
        return DB::transaction(function () use ($attributes){
            $created = Airline::create([
                'name' => data_get($attributes, 'name'),
                'code' => data_get($attributes, 'code'),
            ]);

            if (!$created){
                throw new GeneralJsonException('Failed to create resource.');
            }
            return $created;
        });

    }

    /**
     * @param Airline $airline
     * @param array $attributes
     * @return Airline
     */
    public function update(Airline $airline, array $attributes)
    {
        return DB::transaction(function () use ($airline, $attributes){
            $updated = $airline->update([
                'name' => data_get($attributes, 'name', $airline->name),
                'code' => data_get($attributes, 'code', $airline->code),
            ]);

            if (!$updated){
                throw new GeneralJsonException('Failed to update resource.');
            }
            return $airline;
        });
    }

    /**
     * @param Airline $airline
     * @return bool
     */
    public function delete(Airline $airline)
    {
        return DB::transaction(function () use ($airline){
            $deleted = $airline->delete();

            if (!$deleted){
                throw new GeneralJsonException('Failed to delete resource.');
            }
            return $deleted;
        });
    }

}
