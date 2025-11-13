<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Order;
use App\Models\OrderDetail;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_get_order_list()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Order::factory()->count(2)->create(['user_id' => $user->id]);

        $response = $this->actingAs($user)->getJson('/api/order');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['id', 'user_id', 'status', 'order_date', 'details']]]);
    }

    public function test_cusotmer_creates_order_with_details()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['stock' => 10]);

        $payload = [
            'user_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'total' => 199.99,
            'details' => [
                [
                    'product_id' => $product->id,
                    'pieces' => 2,
                    'unit_price' => 99.99
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/order', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['user_id' => $user->id])
                 ->assertJsonStructure(['details' => [['product_id', 'pieces', 'unit_price']]]);
    }

    public function test_customer_cannot_order_if_stock_is_insufficient()
    {
        $user = User::factory()->create(['role' => 'customer']);
        $product = Product::factory()->create(['stock' => 1]);

        $payload = [
            'user_id' => $user->id,
            'order_date' => now()->toDateString(),
            'status' => 'pending',
            'total' => $product->price * 5,
            'details' => [
                [
                    'product_id' => $product->id,
                    'pieces' => 5,
                    'unit_price' => $product->price
                ]
            ]
        ];

        $response = $this->actingAs($user)->postJson('/api/order', $payload);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors' => ['stock']]);
    }

    public function test_user_get_orders_with_details()
    {
        $user = User::factory()->create();
        $order = Order::factory()->create();
        $product = Product::factory()->create();
        OrderDetail::factory()->create(['order_id' => $order->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->getJson("/api/order/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'user', 'details' => [['product']]]);
    }

    public function test_search_filters_by_email_or_id()
    {
        $admin = User::factory()->create(['role' => 'vendor']);
        $customer = User::factory()->create(['email' => 'test@example.com']);
        Order::factory()->create(['user_id' => $customer->id]);

        $response = $this->actingAs($admin)->getJson('/api/order/search?q=test@example.com');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['user']]]);
    }

    public function test_user_modifies_order_fields()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $order = Order::factory()->create(['status' => 'pending', 'total' => 100]);

        $response = $this->actingAs($vendor)->putJson("/api/order/{$order->id}", [
            'status' => 'delivered',
            'total' => 150
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['status' => 'delivered', 'total' => 150]);
    }

    public function test_destroy_deletes_order()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $order = Order::factory()->create();

        $response = $this->actingAs($vendor)->deleteJson("/api/order/{$order->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Order deleted']);

        $this->assertDatabaseMissing('order', ['id' => $order->id]);
    }

    public function test_checkout_creates_order()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $product = Product::factory()->create(['stock'=>5]);

        $this->actingAs($user)->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response = $this->actingAs($user)->postJson('/api/order/checkout');
        $response->assertStatus(201)
                 ->assertJsonStructure(['id','details']);
    }

    public function test_checkout_fails_if_cart_is_empty()
    {
        $user = User::factory()->create(['role' => 'customer']);
        Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);

        $response = $this->actingAs($user)->postJson('/api/order/checkout');

        $response->assertStatus(400)
                 ->assertJsonFragment(['message' => 'Cart is empty']);
    }
}
