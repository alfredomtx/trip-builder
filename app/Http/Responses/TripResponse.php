<?php

namespace App\Http\Responses;

use JsonSerializable;

class TripResponse implements JsonSerializable
{
    private string $price;
    private array $flights;

    public function toArray(): array
    {
        return [
            'price' => $this->price,
            'flights' => $this->flights,
        ];
    }

    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    public function __construct()
    {
        $this->price = 0;
        $this->flights = [];
    }

    /**
     * @param float $price
     */
    public function addPrice(float $price)
    {
        $this->price = floatval($this->price) + $price;
        $this->price = format_flight_price($this->price);
        return $this;
    }

    /**
     * @param FlightResponse $flight
     */
    public function addFlight(FlightResponse $flight)
    {
        $this->flights[] = $flight;
        return $this;
    }

}
