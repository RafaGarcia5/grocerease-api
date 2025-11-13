<?php

namespace App\Http\Controllers;

use App\Models\OrderDetail;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class OrderDetailController extends Controller
{
    public function index()
    {
        return OrderDetail::with(['order', 'product'])->get();
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:order,id',
            'product_id' => 'required|exists:product,id',
            'pieces' => 'required|integer|min:1',
            'unit_price' => 'required|numeric|min:0',
        ]);

        return OrderDetail::create($validated);
    }

    public function show($id)
    {
        return OrderDetail::with(['order', 'product'])->find($id);
    }

    public function update(Request $request, $id)
    {
        $detail = OrderDetail::findOrFail($id);

        $validated = $request->validate([
            'order_id' => 'sometimes|exists:order,id',
            'product_id' => 'sometimes|exists:product,id',
            'pieces' => 'sometimes|integer|min:1',
            'unit_price' => 'sometimes|numeric|min:0',
        ]);

        $detail->update($validated);
        return $detail;
    }

    public function destroy($id)
    {
        $detail = OrderDetail::findOrFail($id)->delete();

        return response()->json(['message' => 'Order detail deleted successfully'], 200);
    }
}
