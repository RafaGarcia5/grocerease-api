<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Product;
use App\Models\Cart;
use App\Models\CartItem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CartTest extends TestCase
{
    use RefreshDatabase;

    public function test_get_cart_with_items()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $product = Product::factory()->create();
        CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->getJson('/api/cart');

        $response->assertStatus(200)
                 ->assertJsonStructure([ 'id', 'user_id', 'status', 'items' ]);
    }
    
    public function test_get_created_cart_empty()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);

        $response = $this->actingAs($user)->getJson('/api/cart');

        $response->assertStatus(201)
                 ->assertJsonStructure([ 'id', 'user_id', 'status', 'items' ]);
    }

    public function test_add_item_to_cart()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $product = Product::factory()->create([ 'stock' => 10 ]);

        $response = $this->actingAs($user)->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 2
        ]);

        $response->assertStatus(201)
                 ->assertJsonFragment([ 'product_id' => $product->id, 'quantity' => 2 ]);
    }
    
    public function test_add_item_to_cart_fails()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $product = Product::factory()->create([ 'stock' => 0 ]);

        $response = $this->actingAs($user)->postJson('/api/cart/add', [
            'product_id' => $product->id,
            'quantity' => 1
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([ 'message', 'errors' => ['stock'] ]);
    }

    public function test_update_item_quantity()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $product = Product::factory()->create(['stock' => 5]);
        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->putJson("/api/cart/item/{$item->id}", [
            'quantity' => 3
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['quantity' => 3]);
    }

    public function test_update_item_fails_due_to_insufficient_stock()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $product = Product::factory()->create(['stock' => 1]);
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id, 'quantity' => 1]);

        $response = $this->actingAs($user)->putJson("/api/cart/item/{$item->id}", [
            'quantity' => 5
        ]);

        $response->assertStatus(422)
                 ->assertJsonStructure([ 'message', 'errors' => ['stock'] ]);
    }

    public function test_remove_item_from_cart()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        $product = Product::factory()->create();
        $item = CartItem::factory()->create(['cart_id' => $cart->id, 'product_id' => $product->id]);

        $response = $this->actingAs($user)->deleteJson("/api/cart/item/{$item->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Item removed']);
    }

    public function test_clear_cart_removes_all_items()
    {
        $user = User::factory()->create([ 'role' => 'customer' ]);
        $cart = Cart::factory()->create(['user_id' => $user->id, 'status' => 'active']);
        CartItem::factory()->count(3)->create(['cart_id' => $cart->id]);

        $response = $this->actingAs($user)->deleteJson('/api/cart/clear');

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Cart cleared']);

        $this->assertDatabaseMissing('cart_item', ['cart_id' => $cart->id]);
    }
}
