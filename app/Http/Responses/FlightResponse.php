<?php

namespace App\Http\Responses;

use App\Models\Flight;
use DateTime;
use JsonSerializable;
use ReflectionClass;
use ReflectionProperty;

class FlightResponse implements JsonSerializable
{

    private string $airline;
    private string $number;
    private string $departureAirport;
    private string $departureDatetime;
    private string $arrivalAirport;
    private string $arrivalDatetime;
    private string $price;

    /**
     * @param string $departureAirport
     * @return FlightResponse
     */
    public function setDepartureAirport(string $departureAirport)
    {
        $this->departureAirport = $departureAirport;
        return $this;
    }

    /**
     * @param string $airlineCode
     * @return FlightResponse
     */
    public function setAirline(string $airlineCode)
    {
        $this->airline = $airlineCode;
        return $this;
    }

    /**
     * @param int $number
     * @return FlightResponse
     */
    public function setNumber(int $number)
    {
        $this->number = $number;
        return $this;
    }

    /**
     * @param string $price
     * @return FlightResponse
     */
    public function setPrice(string $price)
    {
        $this->price = format_flight_price($price);
        return $this;
    }

    /**
     * @param string $arrivalDate
     * @param string $arrivalTime
     * @return FlightResponse
     */
    public function setArrivalDatetime(string $arrivalDate, string $arrivalTime)
    {
        $this->arrivalDatetime = $this->formatFlightDatetime($arrivalDate, $arrivalTime);
        return $this;
    }

    /**
     * @param string $arrivalAirport
     * @return FlightResponse
     */
    public function setArrivalAirport(string $arrivalAirport)
    {
        $this->arrivalAirport = $arrivalAirport;
        return $this;
    }

    /**
     * @param string $departureDate
     * @param string $departureTime
     * @return FlightResponse
     */
    public function setDepartureDatetime(string $departureDate, string $departureTime)
    {
        $this->departureDatetime = $this->formatFlightDatetime($departureDate, $departureTime);
        return $this;
    }

    /**
     * @return string
     */
    public function getPrice()
    {
        return $this->price;
    }

    /**
     * @param string $date
     * @param string $time
     * @return string
     */
    private function formatFlightDatetime(string $date, string $time)
    {
        $dateFormat = DateTime::createFromFormat("Y-m-d H:i:s", trim("$date $time"));
        return $dateFormat->format("Y-m-d H:i");
    }

    /**
     * @return array
     */
    public function toArray()
    {
        return [
            'airline' => $this->airline,
            'number' => $this->number,
            'departure_airport' => $this->departureAirport,
            'departure_datetime' => $this->departureDatetime,
            'arrival_airport' => $this->arrivalAirport,
            'arrival_datetime' => $this->arrivalDatetime,
            'price' => $this->price,
        ];
    }

    /**
     * @return array
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * Create a new `FlightResponse` instance from a `Flight` model
     *
     * @param Flight $flight
     * @return FlightResponse
     */
    public static function fromFlight(Flight $flight)
    {
        $flightResponse = new FlightResponse();
        $flightResponse->setAirline($flight->airline()->first()->code)
            ->setNumber($flight->number)
            ->setDepartureAirport($flight->departureAirport()->first()->code)
            ->setDepartureDatetime($flight->departure_date, $flight->departure_time)
            ->setArrivalAirport($flight->arrivalAirport()->first()->code)
            ->setArrivalDatetime($flight->arrival_date, $flight->arrival_time)
            ->setPrice($flight->price);
        return $flightResponse;
    }

    /**
     * Get all private attributes of the class.
     *
     * @return array
     */
    public static function getAttributes()
    {
        $reflect = new ReflectionClass(__CLASS__);
        $attributes = [];
        foreach ($reflect->getProperties(ReflectionProperty::IS_PRIVATE) as $attribute) {
            $attributes[] = $attribute;
        }
        return $attributes;
    }


}
