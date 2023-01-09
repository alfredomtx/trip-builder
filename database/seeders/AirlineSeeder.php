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

        Airline::factory()->create(['name' => "Air Canada", 'code' => 'AC']);
        Airline::factory()->create(['name' => "LATAM Brasil", 'code' => 'JJ']);
        Airline::factory()->create(['name' => "Emirates Airlines", 'code' => 'EK']);

        Airline::factory(20)->create();
    }

}
