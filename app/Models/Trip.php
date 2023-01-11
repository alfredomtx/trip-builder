<?php

namespace App\Models;

class Trip
{

    private string $airportCode;
    private array $flights = [];

    /**
     * @param string $airportCode
     * @return Trip
     */
    public function setAirportCode(string $airportCode)
    {
        $this->airportCode = $airportCode;
        return $this;
    }

    /**
     * @param array $flights
     * @return Trip
     */
    public function setFlights(array $flights)
    {
        $this->flights = $flights;
        return $this;
    }

    public function addFlight(array $flight)
    {
        $this->flights[] = $flight;
        return $this;
    }

    /**
     * @param array $flights
     * @return $this
     */
    public function addFlights(array $flights)
    {
        $this->flights = array_merge($this->flights, $flights);
        return $this;
    }

    /**
     * @return string
     */
    public function getAirportCode(): string
    {
        return $this->airportCode;
    }

    /**
     * @return array
     */
    public function getFlights(): array
    {
        return $this->flights;
    }


}
