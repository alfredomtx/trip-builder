<?php

namespace Database\Seeders;

use App\Models\City;
use Database\Seeders\Traits\TruncateTable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CitySeeder extends Seeder
{
    use TruncateTable;
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $this->truncate('cities');

        self::cityHelper('Montreal', 'YMQ', 'America/Montreal');
        self::cityHelper('Toronto', 'YYZ', 'America/Toronto');
        self::cityHelper('Vancouver', 'YVR', 'America/Vancouver');

//        City::factory(5)->create();
    }

    public static function cityHelper(string $name, string $code, string $timezone){
        $city = City::where('code', $code)->first();
        if ($city){
            return $city;
        }
        return City::factory()->create([
            'name' => $name,
            'code' => $code,
            'timezone' => $timezone,
        ]);
    }


}
