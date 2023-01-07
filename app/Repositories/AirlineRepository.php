<?php

namespace App\Repositories;

use App\Exceptions\GeneralJsonException;
use App\Http\Resources\AirlineResource;
use App\Models\Airline;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class AirlineRepository implements BaseRepository
{
    /**
     * @param array $attributes
     * @return mixed
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
     * @return mixed
     * @noinspection PhpHierarchyChecksInspection
     */
    public function update(Airline $airline, array $attributes)
    {
        return DB::transaction(function () use ($airline, $attributes){
            $updated = $airline->update([
                'name' => data_get($attributes, 'name'),
                'code' => data_get($attributes, 'code'),
            ]);

            if (!$updated){
                throw new GeneralJsonException('Failed to update resource.');
            }
            return $updated;
        });
    }

    /**
     * @param Airline $airline
     * @return mixed
     * @noinspection PhpHierarchyChecksInspection
     */
    public function delete(Airline $airline)
    {
        return DB::transaction(function () use ($airline){
            $deleted = $airline->delete();

            if (!$deleted){
                throw new GeneralJsonException('Failed to delete resource.');
            }
        });
    }

}
