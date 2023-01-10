<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Flight extends Model
{
    use HasFactory;

    protected $fillable = [
        'number',
        'price',
        'departure_date',
        'departure_time',
        'arrival_date',
        'arrival_time',
        'airline_id',
        'departure_airport_id',
        'arrival_airport_id',
    ];

    public function airline(): BelongsTo
    {
        return $this->belongsTo(Airline::class, 'airline_id');
    }

    public function departureAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'departure_airport_id');
    }

    /**
     * @return BelongsTo
     */
    public function arrivalAirport(): BelongsTo
    {
        return $this->belongsTo(Airport::class, 'arrival_airport_id');
    }

    /**
     * @param array $filters
     * @return Collection
     */
    public static function searchFlights(array $filters)
    {

        $query = Flight::query();

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

        if ($filters['departure_date'] ?? false){
            $query->where('departure_date', $filters['departure_date']);
        }
        if ($filters['return_date'] ?? false){
            $query->where('return_date', $filters['departure_date']);
        }

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

        if ($filters['departure_date'] ?? false){
            $query->where('departure_date', '>=', $filters['departure_time']);
        }
        if ($filters['arrival_date'] ?? false){
            $query->where('arrival_date', '>=', $filters['departure_time']);
        }

        // die($query->toSql());
    }


}
