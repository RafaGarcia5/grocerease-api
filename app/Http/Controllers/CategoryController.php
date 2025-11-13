<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;


class CategoryController extends Controller
{
    public function index()
    {
        return Category::all();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        return Category::create($validated);
    }

    public function show($id)
    {
        return Category::with('products')->findOrFail($id);
    }

    public function update(Request $request, $id)
    {
        $category = Category::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
        ]);

        $category->update($validated);
        return $category;
    }

    public function destroy($id)
    {
        Category::findOrFail($id)->delete();
        return response()->json(['message' => 'Category deleted']);
    }
}
