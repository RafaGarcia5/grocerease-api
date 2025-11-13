<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function show(Request $request)
    {
        return $request->user();
    }

    public function update(Request $request, $id)
    {
        $user = User::findOrFail($id);

        $validated = $request->validate([
            'name'    => 'sometimes|string|max:255',
            'email'   => 'sometimes|email|unique:users,email,' . $user->id,
            'payment' => 'sometimes|in:cash,card',
            'address' => 'nullable|array',
            'address.addressLine1' => 'nullable|string',
            'address.addressLine2' => 'nullable|string',
            'address.zipCode' => 'nullable|string|max:5',
            'address.colony' => 'nullable|string',
            'address.city' => 'nullable|string',
            'address.state' => 'nullable|string',
        ]);

        $user->update($validated);
        return $user;
    }

    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->delete();
        return response()->json(['message' => 'User deleted successfully']);
    }    
}
