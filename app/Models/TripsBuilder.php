<?php

namespace App\Models;

class TripsBuilder
{

    // store direct flights from the origin to destination. E.g: Montreal -> Vancouver
    public array $directFlights;
    // store flights to destination from other airports that are NOT to origin. E.g: Cornwall -> Vancouver
    public array $toDestination;
    // store flights from origin to stop destinations. E.g: Montreal -> cornwall
    public array $toStopDestinations;



}
