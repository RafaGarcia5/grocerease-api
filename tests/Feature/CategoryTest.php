<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_get_list_of_categories()
    {
        Category::factory()->count(3)->create();

        $response = $this->getJson('/api/category');

        $response->assertStatus(200)
                 ->assertJsonCount(3);
    }

    public function test_user_get_categories_with_products()
    {
        $category = Category::factory()->create();
        Product::factory()->count(2)->create(['category_id' => $category->id]);

        $response = $this->getJson("/api/category/{$category->id}");

        $response->assertStatus(200)
                 ->assertJsonStructure([ 'id', 'name', 'products' => [['id', 'name', 'category_id']] ]);
    }

    public function test_vendor_creates_new_category()
    {
        $vendor = User::factory()->create([ 'role' => 'vendor' ]);

        $response = $this->actingAs($vendor)->postJson('/api/category', [ 'name' => 'Pharmacy' ]);
        
        $response->assertStatus(201)
                 ->assertJsonFragment([ 'name' => 'Pharmacy' ]);
    }

    public function test_vendor_inputs_missing_info()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);

        $response = $this->actingAs($vendor)->postJson('/api/category', []);

        $response->assertStatus(422)
                 ->assertJsonStructure([ 'message', 'errors' => ['name'] ]);
    }

    public function test_vendor_modifies_category_name()
    {
        $vendor = User::factory()->create(['role' => 'vendor']);
        $category = Category::factory()->create(['name' => 'Old Name']);

        $response = $this->actingAs($vendor)->putJson("/api/category/{$category->id}", [
            'name' => 'Updated Name'
        ]);

        $response->assertStatus(200)
                 ->assertJsonFragment(['name' => 'Updated Name']);
    }

    public function test_vednor_delete_category()
    {
        $vendor = User::factory()->create([ 'role' => 'vendor' ]);
        $category = Category::factory()->create();

        $response = $this->actingAs($vendor)->deleteJson("/api/category/{$category->id}");

        $response->assertStatus(200)
                 ->assertJson([ 'message' => 'Category deleted' ]);
    }
}
