<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'iata_code', 'city', 'timezone'];

    public function airport(){
        return $this->hasMany(Airport::class, 'city_id');
    }

}
