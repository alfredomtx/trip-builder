<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use WithFaker;
    use RefreshDatabase;

    public function test_register()
    {
        // Arrange
        $user = [
            'name' => $this->faker->name(),
            'email' => $this->faker->unique()->safeEmail(),
            'password' => 'test123',
            'password_confirmation' => 'test123',
        ];

        // Act
        $response = $this->post('/api/register', $user);

        // Assert
        $response->assertStatus(201);
        $response->assertJsonPath('user.name', $user['name']);
        $response->assertJsonPath('user.email', $user['email']);
    }

    public function test_login()
    {
        // Arrange
        $user = User::factory()->create(['password' => bcrypt('test123')]);

        // Act 1
        $response = $this->post('/api/login', ['email' => $user->email, 'password' => 'test123']);
        $loginResponse = json_decode($response->getContent());

        // Assert 1
        $response->assertStatus(201);

        // the token is long enough
        $this->assertTrue(strlen($loginResponse->token) > 10);
        // assert that the token is in the expected format `userId|token`
        $this->assertStringContainsString('|', $loginResponse->token);
    }


    /**
     * Test if all authenticated endpoints return 401 Unauthorized
     */
    public function test_airline_endpoints(){
        // Arrange
        $authenticatedEndpoints = [
            ['method' => 'GET'      , 'url' => '/api/airlines'],
            ['method' => 'POST'     , 'url' => '/api/airlines'],
            ['method' => 'GET'      , 'url' => '/api/airlines' . '/1'],
            ['method' => 'PUT'      , 'url' => '/api/airlines' . '/1'],
            ['method' => 'DELETE'   , 'url' => '/api/airlines' . '/1'],
        ];

        foreach($authenticatedEndpoints as $endpoint){
            // Act
            $response = $this->json($endpoint['method'], $endpoint['url']);
            // Assert
            $response->assertStatus(401);
        }
    }
}
