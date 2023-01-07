<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class FlightResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  Request  $request
     * @return array
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'number' => $this->number,
            'price' => $this->price,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
            'departure_time' => $this->departure_time,
            'arrival_time' => $this->arrival_time,
            'airline_id' => $this->airline_id,
            'departure_airport_id' => $this->departure_airport_id,
            'arrival_airport_id' => $this->arrival_airport_id,
        ];
    }
}
