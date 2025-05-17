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
        return view('products.index');
    }

    public function orders()
    {
        return view('orders.index');
    }

    public function users()
    {
        return view('users.index');
    }

    public function banners()
    {
        return view('banners.index');
    }
}
