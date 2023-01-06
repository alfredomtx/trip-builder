<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class AirlineFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name();
        return [
            'name' => $name,
            'code' => create_acronym_from_words($name),
        ];
    }
}
