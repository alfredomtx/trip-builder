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
        $name = $this->faker->company();
        // Create the code getting the first letter of each word from $name, and setting as uppercase
        // Delimit by multiple spaces, hyphen, underscore, comma
        $words = preg_split("/[\s,_-]+/", $name);

        $code = '';
        foreach ($words as $w) {
            $code .= mb_substr($w, 0, 1);
        }
        return [
            'name' => $name,
            'iata_code' => strtoupper($code),
        ];
    }
}
