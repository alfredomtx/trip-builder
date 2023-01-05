<?php

namespace Tests\Feature;

use App\Models\Airline;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use TheSeer\Tokenizer\Exception;

use function PHPUnit\Framework\assertTrue;

class AirlineTest extends TestCase
{

    // All authenticated endpoints of Airlines, to be tested in `test_assert_all_airline_endpoints_require_authentication()`
    public static $authenticatedEndpoints = [
        // get all
        ['method' => 'get'      , 'url' => '/api/airlines/'],
        // insert
        ['method' => 'post'     , 'url' => '/api/airlines/'],
        // get by id
        ['method' => 'get'      , 'url' => '/api/airlines/1'],
        // update by id
        ['method' => 'put'      , 'url' => '/api/airlines/1'],
        // delete by id
        ['method' => 'delete'   , 'url' => '/api/airlines/1'],
    ];

    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function test_example()
    {
        $response = $this->get('/');

        $response->assertStatus(200);
    }

    /**
     * Test if all authenticated endpoints of Airline return 401 Unauthorized
     */
    public function test_all_airline_endpoints_require_authentication(){
        foreach(Self::$authenticatedEndpoints as $endpoint){
            // Act
            switch($endpoint['method']){
                case 'get':
                    $response = $this->getJson($endpoint['url']);
                    break;
                case 'post':
                    $response = $this->postJson($endpoint['url']);
                    break;
                case 'put':
                    $response = $this->putJson($endpoint['url']);
                    break;
                case 'delete':
                    $response = $this->deleteJson($endpoint['url']);
                    break;
                default:
                    throw new Exception('Invalid endpoint method: ' . $endpoint['method']);
            }

            // Assert
            $response->assertStatus(401);
        }
    }

    /** 
     * Test if GET returns all airlines
    */
    public function test_get_returns_airlines(){
        // Arrange
        // create 2 Airlines
        $airlines = Airline::factory(2)->create();

        // Act
        $response = $this->getJson('/airlines');

        // Assert
        $response->assertStatus(200);
        // assert we have the same `name` and `iata/code`
        $airlinesResponse = json_decode($response->getContent());
        dd($airlinesResponse);

        // filter from the response only the 2 airlines we added before
        $filteredAirlines = array_filter($airlinesResponse, function ($airline) use ($airlines) {
            if ($airline['name'] == $airlines[0]->name)
                return true;
            return false;
        });

        assertTrue(count($filteredAirlines) == 2);
        // assert both `name` and `iata codes` are the same
        assertTrue($filteredAirlines[0]['name'] == $airlines[0]['name']);
        assertTrue($filteredAirlines[0]['iata_code'] == $airlines[0]['iata_code']);
        assertTrue($filteredAirlines[1]['name'] == $airlines[1]['name']);
        assertTrue($filteredAirlines[1]['iata_code'] == $airlines[1]['iata_code']);


        // Clean
        $this->deleteAirlines($airlines);

    }

    public function deleteAirlines(Airline $airlines){
        foreach ($airlines as $airline){
            Airline::destroy($airline);
        }
    }
}
