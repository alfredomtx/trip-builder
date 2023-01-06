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
            'code' => create_acronym_from_words($name),
            'region_code' => create_acronym_from_words($this->faker->state()),
            'country_code' => $this->faker->countryCode(),
            'timezone' => $this->faker->timezone(),
        ];
    }
}
