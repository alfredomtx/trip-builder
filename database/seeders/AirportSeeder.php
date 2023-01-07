<?php

namespace Database\Seeders;

use App\Models\Airport;
use App\Models\City;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class AirportSeeder extends Seeder
{
    use TruncateTable;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncate('airports');
        Airport::factory(5)->create();
    }
}
