<?php

namespace Database\Factories;

use App\Models\City;
use Illuminate\Database\Eloquent\Factories\Factory;

class AirportFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        $name = $this->faker->name() . " Airport";
        // the airport code is unique on database, so lets create the acronum and also add 3 random letters to it.
        $randomLetters = $this->faker->randomLetter() . $this->faker->randomLetter() . $this->faker->randomLetter();
        $code = strtoupper(create_acronym_from_words($name) . $randomLetters);
        
        return [
            'name' => $name,
            'code' => $code,
            'latitude' => $this->faker->latitude(),
            'longitude' => $this->faker->longitude(),
            // FKs
            'city_id' => City::factory(),
        ];
    }
}
