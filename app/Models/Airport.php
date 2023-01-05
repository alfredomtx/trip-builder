<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Airport extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'iata_code', 'latitute', 'longitude', 'city_id'];

    public function city(){
        return $this->belongsTo(City::class, 'city_id');
    }

}
