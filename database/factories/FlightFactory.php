<?php

namespace Database\Factories;

use App\Models\Airline;
use Illuminate\Database\Eloquent\Factories\Factory;

class FlightFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $number =  $this->faker->numberBetween(000, 9999);
        $price = $this->faker->numberBetween(50, 10000) . '.00';

        return [
            'number' => $number,
            'price' => $price,
            'departure_time' => date('H:i', strtotime($this->faker->time())),
            'arrival_time' => date('H:i', strtotime($this->faker->time())),
        ];
    }
}
