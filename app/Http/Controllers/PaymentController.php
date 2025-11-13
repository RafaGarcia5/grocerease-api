<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Order;
use App\Models\Cart;
use App\Models\Product;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    public function confirm(Request $request)
    {
        $user = $request->user();
        $sessionId = $request->input('session_id');

        if (!$sessionId) {
            return response()->json(['message' => 'Missing session_id'], 400);
        }

        Stripe::setApiKey(env('STRIPE_SECRET'));
        $session = Session::retrieve($sessionId);

        if ($session->payment_status !== 'paid') {
            return response()->json(['status' => 'unpaid']);
        }

        $existing = Order::where('stripe_session_id', $sessionId)->first();
        if ($existing) {
            return response()->json(['status' => 'already_created', 'order' => $existing]);
        }

        $cart = Cart::with('items.product')
            ->where('user_id', $user->id)
            ->where('status', 'active')
            ->firstOrFail();

        $total = 0;
        DB::beginTransaction();
        try {
            $order = Order::create([
                'user_id' => $user->id,
                'order_date' => now(),
                'status' => 'pending',
                'total' => 0,
                'stripe_session_id' => $sessionId,
            ]);

            foreach ($cart->items as $item) {
                if ($item->product->stock < $item->quantity) {
                    throw ValidationException::withMessages([
                        'stock' => "Insufficient stock for {$item->product->name}."
                    ]);
                }

                $order->details()->create([
                    'product_id' => $item->product_id,
                    'pieces' => $item->quantity,
                    'unit_price' => $item->product->price,
                ]);

                $total += $item->product->price * $item->quantity;
                $item->product->decrement('stock', $item->quantity);
            }

            $order->update(['total' => $total]);

            $cart->update(['status' => 'checked_out']);
            $cart->items()->delete();

            DB::commit();

            return response()->json([
                'status' => 'success',
                'order' => $order->load('details.product'),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
