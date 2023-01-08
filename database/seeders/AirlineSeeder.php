<?php

namespace Database\Seeders;

use App\Models\Airline;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirlineSeeder extends Seeder
{
    use TruncateTable;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncate('airlines');

        Airline::factory()->create([
            'name' => "Air Canada",
            'code' => 'AC',
        ]);

//        Airline::factory(5)->create();
    }
}
