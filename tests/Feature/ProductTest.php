<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Category;
use App\Models\Product; 
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_gets_all_products_with_category()
    {
        $category = Category::factory()->create();
        Product::factory()->count(3)->create(['category_id' => $category->id]);

        $response = $this->getJson('/api/product');

        $response->assertStatus(200)
                 ->assertJsonStructure([['id', 'name', 'category' => ['id', 'name']]])
                 ->assertJsonCount(3);
    }

    public function test_show_returns_single_product_with_category()
    {
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/product/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure(['id', 'name', 'category' => ['id', 'name']]);
    }

    public function test_vendor_creates_product_with_valid_data()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();

        $payload = [
            'name' => 'Test Product',
            'description' => 'Test product description',
            'price' => 99.99,
            'stock' => 10,
            'image_url' => 'https://example.com/image.jpg',
            'category_id' => $category->id,
            'status' => 'active'
        ];

        $response = $this->actingAs($vendor)->postJson('/api/product', $payload);

        $response->assertStatus(201)
                 ->assertJsonFragment(['name' => 'Test Product']);

        $this->assertDatabaseHas('product', ['name' => 'Test Product']);
    }

    public function test_vendor_cannot_create_product_with_missing_fields()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $response = $this->actingAs($vendor)->postJson('/api/product', []);

        $response->assertStatus(422)
                 ->assertJsonStructure(['message', 'errors' => ['name', 'description', 'price', 'stock', 'category_id', 'status']]);
    }

    public function test_vendor_modifies_product_fields()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create();
        $product = Product::factory()->create(['category_id' => $category->id]);

        $response = $this->actingAs($vendor)->putJson("/api/product/{$product->id}", [
            'name' => 'Updated Name',
            'price' => 150.5,
            'status' => 'inactive'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name', 'price' => 150.5, 'status' => 'inactive']);
    }

    public function test_vendor_deletes_product()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $product = Product::factory()->create();

        $response = $this->actingAs($vendor)->deleteJson("/api/product/{$product->id}");

        $response->assertStatus(200)
                 ->assertJsonFragment(['message' => 'Product deleted']);
    }

    public function test_search_returns_matching_products()
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'name' => 'Test Item',
            'description' => 'Test description',
            'category_id' => $category->id
        ]);

        $response = $this->getJson('/api/product/search?q=test');

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['name', 'description']]])
                 ->assertJsonFragment(['name' => 'Test Item']);
    }

    public function test_get_by_category_filters_products()
    {
        $category = Category::factory()->create();
        Product::factory()->create([
            'name' => 'Category Match',
            'description' => 'Relevant',
            'category_id' => $category->id
        ]);

        Product::factory()->create([
            'name' => 'Other Category',
            'category_id' => Category::factory()->create()->id
        ]);

        $response = $this->getJson("/api/product/category/{$category->id}?q=match");

        $response->assertStatus(200)
                 ->assertJsonStructure(['data' => [['name', 'category']]])
                 ->assertJsonFragment(['name' => 'Category Match']);
    }
}
