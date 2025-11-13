<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use Illuminate\Http\Request;

class AdminController extends Controller
{
    public function orders()
    {
        // $this->authorize('vendor-only');
        return Order::with(['user', 'details.product'])->get();
    }

    public function users()
    {
        // $this->authorize('vendor-only');
        return User::with('orders')->get();
    }
}

