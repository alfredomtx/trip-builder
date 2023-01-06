<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class CityFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->city();
        return [
            'name' => $name,
            'iata_code' => create_acronym_from_words($name),
            'timezone' => $this->faker->timezone(),
        ];
    }
}
