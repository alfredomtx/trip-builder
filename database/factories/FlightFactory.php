<?php

namespace Database\Factories;

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
        $acronym = create_acronym_from_words($this->faker->name());
        $number =  $acronym . $this->faker->numberBetween(100, 999);
        $price = $this->faker->numberBetween(50, 10000) . '.00';

        return [
            'number' => $number,
            'price' => $price,
        ];
    }
}
