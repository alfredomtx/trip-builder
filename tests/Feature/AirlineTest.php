<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Airline;
use TheSeer\Tokenizer\Exception;

use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Foundation\Testing\RefreshDatabase;

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
        $user = User::factory()->create();
        // create 2 Airlines
        $airlines = Airline::factory(2)->create();

        // Act
        $response = $this->actingAs($user)->getJson('/api/airlines');

        // Assert
        $response->assertStatus(200);
        // assert we have the same `name` and `iata/code`
        $airlinesResponse = json_decode($response->getContent(), true);

        // filter from the response only the 2 airlines we added before
        $filteredAirlines = array_filter($airlinesResponse['data'], function ($airlinesResponse) use ($airlines) {
            foreach ($airlines as $airline){
                if ($airlinesResponse['name'] == $airline->name){
                    return true;
                }
            }
            return false;
        });

        // assert 2 airlines have been added
        $this->assertTrue(count($filteredAirlines) == 2);

        // assert both `name` and `iata codes` are the same
        for ($i=0; $i < count($airlines) - 1; $i++) { 
            $airline = $airlines[$i];
            $airlineResponse = $filteredAirlines[$i];
            $this->assertEquals($airline['name'], $airlineResponse['name']);
            $this->assertEquals($airline['code'], $airlineResponse['code']);
        }
    }

}
