<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_user_information()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson("/api/user/{$user->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment([
                     'id' => $user->id,
                     'email' => $user->email,
                     'name' => $user->name,
                     'address' => $user->address,
                 ]);
    }

    public function test_update_user_information()
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->putJson("/api/user/{$user->id}", ['name' => 'Updated Name']);
        
        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_update_fails_with_invalid_email()
    {
        $user = User::factory()->create();
        $other = User::factory()->create(['email' => 'taken@example.com']);

        $response = $this->actingAs($user)->putJson("/api/user/{$user->id}", [
            'email' => 'taken@example.com'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors' => ['email']]);
    }

    public function test_delete_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/user/{$user->id}");

        $response->assertStatus(200)
                 ->assertJson(['message' => 'User deleted successfully']);
    }
}
