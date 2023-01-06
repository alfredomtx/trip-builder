<?php

namespace Database\Seeders;

use App\Models\Airline;
use App\Models\Airport;
use App\Models\City;
use App\Models\Flight;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
	/**
	 * Seed the application's database.
	 *
	 * @return void
	 */
	public function run()
	{
		User::factory(5)->create();

		$this->call([
			CitySeeder::class,
			AirportSeeder::class,
			AirlineSeeder::class,
			FlightSeeder::class,
		]);

	}
}
