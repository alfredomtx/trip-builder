<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = ['number', 'price', 'departure_time', 'arrival_time', 'airline_id', 'departure_airport_id', 'arrival_airport_id'];

    public function airline(){
        return $this->belongsTo(Airline::class, 'airline_id');
    }
    public function departureAirport(){
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }
    public function arrivalAirport(){
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

}
