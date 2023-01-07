<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class City extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'code', 'region_code', 'country_code', 'timezone'];

    public function airports(){
        return $this->hasMany(Airport::class, 'city_id');
    }

}
