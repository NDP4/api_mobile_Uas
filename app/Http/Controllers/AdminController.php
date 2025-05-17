<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\Order;
use App\Models\User;
use App\Models\Banner;

class AdminController extends Controller
{
    public function products()
    {
        $products = Product::with(['images', 'variants'])->get();
        return response()->json(['status' => 1, 'products' => $products]);
    }

    public function orders()
    {
        $orders = Order::with(['user', 'items.product'])->get();
        return response()->json(['status' => 1, 'orders' => $orders]);
    }

    public function users()
    {
        $users = User::all();
        return response()->json(['status' => 1, 'users' => $users]);
    }

    public function banners()
    {
        $banners = Banner::with('images')->get();
        return response()->json(['status' => 1, 'banners' => $banners]);
    }
}
