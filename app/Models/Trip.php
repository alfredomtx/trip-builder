<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trip extends Model
{
    use HasFactory;

    protected $fillable = ['departure_airport', 'arrival_airport', 'departure_date', 'return_date', 'trip_type'];
}
