<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CartController extends Controller
{
    public function index(Request $request) {
        $user = $request->user();
        $cart = Cart::firstOrCreate(['user_id' => $user->id, 'status' => 'active']);
        return $cart->load('items.product.category');
    }
    
    public function addItem(Request $request) {
        $user = $request->user();
        $validated = $request->validate([
            'product_id' => 'required|exists:product,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $cart = Cart::firstOrCreate(['user_id' => $user->id, 'status' => 'active']);
        $product = Product::find($validated['product_id']);

        if($product->stock < $validated['quantity']) {
            throw ValidationException::withMessages([
                'stock' => "Insufficient stock for {$product->name}."
            ]);
        }

        $item = $cart->items()->where('product_id', $validated['product_id'])->first();

        if($item) {
            $newQuantity = $item->quantity + $validated['quantity'];
            if($product->stock < $newQuantity) {
                throw ValidationException::withMessages([
                    'stock' => "Not enough stock for {$product->name}."
                ]);
            }
            $item->quantity = $newQuantity;
            $item->save();
        } else {
            $item = $cart->items()->create($validated);
        }

        return $item->load('product');
    }

    public function updateItem(Request $request, $id) {
        $validated = $request->validate([
            'quantity' => 'required|integer|min:1',
        ]);

        $item = CartItem::findOrFail($id);
        $product = $item->product;

        if ($product->stock < $validated['quantity']) {
            throw ValidationException::withMessages([
                'stock' => "Insufficient stock for {$product->name}."
            ]);
        }

        $item->update(['quantity' => $validated['quantity']]);
        return $item->load('product');
    }

    public function removeItem($id) {
        CartItem::findOrFail($id)->delete();
        return response()->json(['message' => 'Item removed']);
    }

    public function clear(Request $request) {
        $user = $request->user();
        $cart = Cart::where('user_id', $user->id)->where('status', 'active')->first();
        if ($cart) {
            $cart->items()->delete();
        }
        return response()->json(['message' => 'Cart cleared']);
    }
}
