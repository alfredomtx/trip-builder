<?php

namespace App\Models;

class FlightSearch
{
    public string $destination;
    public string $origin;
    public string $airlineCode;
    public string $departureDate;


    /**
     * @param string $origin
     * @return FlightSearch
     */
    public function setOrigin(string $origin)
    {
        $this->origin = $origin;
        return $this;
    }

    /**
     * @param string $departureDate
     * @return FlightSearch
     */
    public function setDepartureDate(string $departureDate)
    {
        $this->departureDate = $departureDate;
        return $this;
    }

    /**
     * @param string $destination
     * @return FlightSearch
     */
    public function setDestination(string $destination)
    {
        $this->destination = $destination;
        return $this;
    }

    /**
     * @param string $airlineCode
     * @return FlightSearch
     */
    public function setAirlineCode(string $airlineCode)
    {
        $this->airlineCode = $airlineCode;
        return $this;
    }


}
