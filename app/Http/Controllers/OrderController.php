<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Product;
use App\Models\Cart;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Stripe\Stripe;
use Stripe\Checkout\Session;

class OrderController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $perPage = $request->query('per_page', 30);
        $status = $request->query('status');
        $sortDir = $request->query('sort_dir', 'desc'); 

        $query = Order::with(['details.product']);

        if ($user->hasRole('admin') || $user->hasRole('vendor')) {
            $query->with('user');
        } else {
            $query->where('user_id', $user->id);
        }

        if ($status) {
            $query->where('status', $status);
        }

        $query->orderBy('order_date', $sortDir);
        
        return $query->paginate($perPage);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'user_id'    => 'required|exists:users,id',
            'order_date' => 'required|date',
            'status'     => 'required|in:pending,send,delivered,cancel',
            'total'      => 'required|numeric|min:0',
            'details'    => 'required|array',
            'details.*.product_id' => 'required|exists:product,id',
            'details.*.pieces'     => 'required|integer|min:1',
            'details.*.unit_price' => 'required|numeric|min:0',
        ]);

        return DB::transaction(function () use ($validated) {
            $order = Order::create([
                'user_id'    => $validated['user_id'],
                'order_date' => $validated['order_date'],
                'status'     => $validated['status'],
                'total'      => $validated['total'],
            ]);

            foreach ($validated['details'] as $detail) {
                $product = Product::find($detail['product_id']);

                if (!$product) {
                    throw new \Exception("Product not found");
                }

                if ($product->stock < $detail['pieces']) {
                    throw ValidationException::withMessages([
                        'stock' => "There is not enough stock for the product {$product->name}. Available: {$product->stock}, requested: {$detail['pieces']}"
                    ]);
                }

                $order->details()->create([
                    'product_id' => $detail['product_id'],
                    'pieces'     => $detail['pieces'],
                    'unit_price' => $detail['unit_price'],
                ]);

                $product->stock -= $detail['pieces'];
                $product->save();
            }

            return $order->load(['details.product']);
        });
    }

    public function show($id)
    {
        return Order::with(['user', 'details.product'])->findOrFail($id);
    }

    public function search(Request $request)
    {
        $query = Order::with(['user', 'details.product']);

        if ($term = $request->query('q')) {
            $query->whereHas('user', function ($q) use ($term) {
                $q->where('email', 'like', "%{$term}%");
            })
            ->orWhere('id', 'like', "%{$term}%");
        }

        $sortBy = $request->query('sort_by', 'order_date');
        $sortDir = $request->query('sort_dir', 'desc');
        $query->orderBy($sortBy, $sortDir);

        $perPage = $request->query('per_page', 10);
        return $query->paginate($perPage);
    }

    public function update(Request $request, $id)
    {
        $order = Order::findOrFail($id);

        $validated = $request->validate([
            'status' => 'sometimes|in:pending,send,delivered,cancel',
            'total'  => 'sometimes|numeric|min:0',
        ]);

        $order->update($validated);
        return $order;
    }

    public function destroy($id)
    {
        Order::findOrFail($id)->delete();
        return response()->json(['message' => 'Order deleted']);
    }

    public function checkout(Request $request)
    {
        $user = $request->user();
        $cart = Cart::with('items.product')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->first();

        if (!$cart || $cart->items->isEmpty()) {
            return response()->json(['message' => 'Cart is empty'], 400);
        }

        $lineItems = [];
        $total = 0;

        foreach ($cart->items as $item) {
            if ($item->product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'stock' => "Insufficient stock for {$item->product->name}."
                ]);
            }

            $lineItems[] = [
                'price_data' => [
                    'currency' => 'usd',
                    'product_data' => [
                        'name' => $item->product->name,
                    ],
                    'unit_amount' => (int) ($item->product->price * 100), // in cents
                ],
                'quantity' => $item->quantity,
            ];

            $total += $item->product->price * $item->quantity;
        }

        // // Create order
        // $order = Order::create([
        //     'user_id' => $user->id,
        //     'order_date' => now(),
        //     'status' => 'pending',
        //     'total' => $total,
        // ]);

        // foreach ($cart->items as $item) {
        //     $order->details()->create([
        //         'product_id' => $item->product_id,
        //         'pieces' => $item->quantity,
        //         'unit_price' => $item->product->price,
        //     ]);
        //     $item->product->decrement('stock', $item->quantity);
            
        //     if ($item->product->stock - $item->quantity <= 0) {
        //         $item->product->update(['status' => 'inactive']);
        //     }
        // }

        $frontendUrl = env('FRONTEND_URL');

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => $lineItems,
            'mode' => 'payment',
            'success_url' => $frontendUrl . '/orders',
            'cancel_url' => $frontendUrl . '/cart',
            // 'metadata' => [
            //     'order_id' => $order->id,
            // ],
        ]);

        // $cart->update(['status' => 'checked_out']);
        // $cart->items()->delete();

        return response()->json([
            'checkout_url' => $session->url,
            'session_id' => $session->id,
            // 'order' => $order->load('details.product'),
        ]);
        // return $order->load('details.product');
    }
}
