<?php

namespace App\Models;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'price',
        'departure_time',
        'arrival_time',
        'airline_id',
        'departure_airport_id',
        'arrival_airport_id'
    ];

    /**
     * Summary of searchFlights
     * @param array $filters
     * @return Collection
     */
    
    public static function searchFlights(array $filters): Collection {

        $query = Flight::select(
                'number',
                'flights.id AS flight_id',
            );

        // joining and filtering with the `departure airport`
        $query->join('airports AS departure_airport', function ($join) use ($filters) {
            $join->on('departure_airport_id', 'departure_airport.id')
                ->where('departure_airport.code', $filters['departure_airport']);
        });

        // joining and filtering with the `arrival airport`
        $query->join('airports AS arrival_airport', function ($join) use ($filters) {
            $join->on('arrival_airport_id', 'arrival_airport.id')
                ->where('arrival_airport.code', $filters['arrival_airport']);
        });
    

            
        if ($filters['paginate'] ?? false){
            $query->paginate(10);
        }

        die($query->toSql());

        return $query->get();

    }

    public function scopeFilter($query, array $filters){
        // joining and filtering with the `departure airport`
        $query->join('airports AS departure_airport', function ($join) use ($filters) {
            $join->on('departure_airport_id', 'departure_airport.id')
                ->where('departure_airport.code', $filters['departure_airport']);
        });

        // joining and filtering with the `arrival airport`
        $query->join('airports AS arrival_airport', function ($join) use ($filters) {
            $join->on('arrival_airport_id', 'arrival_airport.id')
                ->where('arrival_airport.code', $filters['arrival_airport']);
        });

        if ($filters['departure_time'] ?? false){
            $query->where('departure_time', '>=', $filters['departure_time']);
        }

        // if ($filters['paginate'] ?? false){
        //     $query->paginate(10);
        // }
        // die($query->toSql());
    }

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
