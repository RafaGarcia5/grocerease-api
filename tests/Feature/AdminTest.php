<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Order;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class AdminTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_get_all_orders()
    {
        $vendor = User::factory()->create([ 'role' => 'vendor' ]);
        Order::factory(3)->create();

        $response = $this->actingAs($vendor)->getJson('/api/admin/orders');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_customer_cannot_get_all_orders()
    {
        $customer = User::factory()->create([ 'role' => 'customer' ]);
        Order::factory(3)->create();

        $response = $this->actingAs($customer)->getJson('/api/admin/orders');
        $response->assertStatus(403);
    }

    public function test_admin_can_get_users()
    {
        $vendor = User::factory()->create([ 'role' => 'vendor' ]);
        User::factory(2)->create();

        $response = $this->actingAs($vendor)->getJson('/api/admin/users');
        $response->assertStatus(200)->assertJsonCount(3);
    }

    public function test_customer_cannot_get_users()
    {
        $customer = User::factory()->create([ 'role' => 'customer' ]);
        User::factory(2)->create();

        $response = $this->actingAs($customer)->getJson('/api/admin/users');
        $response->assertStatus(403);
    }
}
