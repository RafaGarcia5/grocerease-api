<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    public function index()
    {
        return Product::with('category')->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'        => 'required|string|max:255',
            'description' => 'required|string',
            'price'       => 'required|numeric|min:0',
            'stock'       => 'required|integer|min:0',
            'image_url'   => 'nullable|string',
            'category_id' => 'required|exists:category,id',
            'status'      => 'required|in:active,inactive',
        ]);

        return Product::create($validated);
    }

    public function show($id)
    {
        return Product::with('category')->findOrFail($id);
    }

    public function search(Request $request)
    {
        $term = $request->query('q');
        $perPage = $request->query('per_page', 30);

        $products = Product::with('category')
            ->where('name', 'LIKE', "%{$term}%")
            ->orWhere('description', 'LIKE', "%{$term}%")
            ->paginate($perPage);

        return response()->json($products);
    }

    public function getByCategory($categoryId, Request $request)
    {
        $term = $request->query('q');
        $perPage = $request->query('per_page', 30);

        $products = Product::where('category_id', $categoryId)
                        ->with('category')
                        ->where(function($query) use ($term) {
                            $query->where('name', 'LIKE', "%{$term}%")
                                ->orWhere('description', 'LIKE', "%{$term}%");
                        })
                        ->paginate($perPage);

        return response()->json($products);
    }

    public function update(Request $request, $id)
    {
        $product = Product::findOrFail($id);

        $validated = $request->validate([
            'name'        => 'sometimes|string|max:255',
            'description' => 'sometimes|string',
            'price'       => 'sometimes|numeric|min:0',
            'stock'       => 'sometimes|integer|min:0',
            'image_url'   => 'nullable|string',
            'category_id' => 'sometimes|exists:category,id',
            'status'      => 'sometimes|in:active,inactive',
        ]);

        $product->update($validated);
        return $product;
    }

    public function destroy($id)
    {
        Product::findOrFail($id)->delete();
        return response()->json(['message' => 'Product deleted']);
    }
}
