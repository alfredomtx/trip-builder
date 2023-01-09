<?php

namespace Api;

use App\Models\Airline;
use App\Models\User;
use Exception;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AirlineApiTest extends TestCase
{
    use RefreshDatabase;

    const URI = '/api/airlines';

    public function setUp(): void
    {
        parent::setUp();
        $user = User::factory()->create();
        $this->actingAs($user);
    }

    public function test_index(){
        // Arrange
        $numberOfResources = 5;
        $dummies = Airline::factory($numberOfResources)->create();
        $dummiesIds = $dummies->map(fn ($dummy) => $dummy->id)->toArray();

        // Act
        $response = $this->getJson(self::URI);

        $data = $response->assertStatus(200)->json('data');

        // Assert
        // pagination
        $this->assertEquals(1, $response->json('meta.current_page'));
        // same number of resources
        $this->assertTrue(count($data) === $numberOfResources);
        // loop through and check if each `id` from the resources returned are in the resources created before
        collect($data)->each(function ($airline) use ($dummiesIds) {
            $this->assertTrue(in_array($airline['id'], $dummiesIds),
                '`id` ' . $airline['id']  . ' is missing in the response.'
            );
        });
    }

    public function test_show(){
        // Arrange
        $dummy = Airline::factory()->create();

        // Act
        $response = $this->getJson(self::URI . '/' . $dummy->id);

        // Assert
        $result = $response->assertStatus(200)->json('data');
        $this->assertEquals($dummy->id, $result['id'], 'Response ID not the same as model id.');
    }

    public function test_create(){
        // Arrange
        $dummy = Airline::factory()->make();

        // Act
        $response = $this->postJson(self::URI, $dummy->toArray());

        // Assert
        $result = $response->assertStatus(201)->json('data');
        // convert result getting only the keys that we have in our dummy
        $attributes = collect($result)->only(array_keys($dummy->getAttributes()));

        // loop through and make sure every value exists and is the same
        $attributes->each(function ($value, $key) use ($dummy) {
            $this->assertSame($dummy[$key], $value, 'Value is not the same');
        });
    }

    public function test_update(){
        // Arrange
        $user = User::factory()->create();
        $dummy = Airline::factory()->create();
        $dummy2 = Airline::factory()->make();

        // create a new instance of the Resource with all fillable attributes
        $fillable = (new Airline)->getFillable();
        $fillables = collect($fillable);

        // Act
        // loop through every `fillable` attribute and update `$dummy` with new values from `$dummy2`
        $fillables->each(function ($attribute) use ($dummy, $dummy2, $user){
            $body = [
                $attribute => $dummy2[$attribute],
            ];
            $response = $this->json('PUT', self::URI . '/' . $dummy->id, $body);

            // Assert
            $updatedResource = $response->assertStatus(200)->json('data');
            $this->assertSame($dummy2[$attribute], $updatedResource[$attribute],
                "Failed to update model attribute `{$attribute}`."
            );
        });
    }

    public function test_delete(){
        // Arrange
        $dummy = Airline::factory()->create();

        // Act 1
        $response = $this->deleteJson(self::URI . '/' . $dummy->id);

        // Assert 1
        $result = $response->assertStatus(204);

        // Act 2
        // Request for the same resource, it should not be found
        $this->getJson(self::URI . '/' . $dummy->id)->assertStatus(404);
    }

}
