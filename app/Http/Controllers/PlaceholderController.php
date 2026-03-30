<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PlaceholderController extends Controller
{
    public function inventoryItems()
    {
        return view('placeholders.inventory_items');
    }

    public function inventoryIn()
    {
        return view('placeholders.inventory_in');
    }

    public function inventoryOut()
    {
        return view('placeholders.inventory_out');
    }

    public function stok()
    {
        return view('placeholders.stok');
    }
}