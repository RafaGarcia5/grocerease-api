<?php

namespace Tests\Feature;

use App\Models\OrderDetail;
use App\Models\User;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderDetailTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_all_order_details()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        OrderDetail::factory()->count(3)->create([
            'order_id' => $order->id,
            'product_id' => $product->id
        ]);

        $response = $this->actingAs($user)->getJson('/api/order-details');

        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'order_id', 'product_id', 'pieces', 'unit_price', 'order', 'product']])
                 ->assertJsonCount(3);
    }
    
    public function test_index_fails_due_to_unauthorized()
    {
        $response = $this->getJson('/api/order-details');

        $response->assertStatus(401);
    }

    public function test_show_returns_single_order_detail()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        $detail = OrderDetail::factory()->create([
            'order_id' => $order->id,
            'product_id' => $product->id
        ]);

        $response = $this->actingAs($user)->getJson("/api/order-details/{$detail->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'order_id', 'product_id', 'pieces', 'unit_price', 'order', 'product']);
    }

    public function test_store_creates_order_detail()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $order = Order::factory()->create();
        $product = Product::factory()->create();

        $response = $this->actingAs($user)->postJson('/api/order-details', [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'pieces' => 2,
            'unit_price' => 99.99
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment([
                     'order_id' => $order->id,
                     'product_id' => $product->id,
                     'pieces' => 2,
                     'unit_price' => 99.99
                 ]);
    }

    public function test_store_requires_all_fields()
    {
        $user = User::factory()->create(['role' => 'customer']);

        $response = $this->actingAs($user)->postJson('/api/order-details', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors']);
    }

    public function test_update_modifies_order_detail()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $detail = OrderDetail::factory()->create(['pieces' => 1, 'unit_price' => 50]);

        $response = $this->actingAs($user)->putJson("/api/order-details/{$detail->id}", [
            'pieces' => 5,
            'unit_price' => 75.5
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['pieces' => 5, 'unit_price' => 75.5]);
    }

    public function test_destroy_deletes_order_detail()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $detail = OrderDetail::factory()->create();

        $response = $this->actingAs($user)->deleteJson("/api/order-details/{$detail->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Order detail deleted successfully']);

        $this->assertDatabaseMissing('order_details', ['id' => $detail->id]);
    }
}
