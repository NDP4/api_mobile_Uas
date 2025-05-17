<?php

namespace App\Http\Controllers;

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
