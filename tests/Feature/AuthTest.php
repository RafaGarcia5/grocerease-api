<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Rafael Garcia',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
            'role' => 'customer',
            'payment' => 'cash'
        ]);

        $response->assertStatus(201)
                 ->assertJsonStructure(['user', 'token']);
    }
    
    public function test_user_input_incomplete_info()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Rafael Garcia',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors']);
    }

    public function test_user_can_login()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(200)
                 ->assertJsonStructure(['user', 'token']);
    }
    
    public function test_user_input_invalid_credentials()
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->postJson('/api/login', [
            'email' => $user->email,
            'password' => 'password'
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors']);
    }
}
