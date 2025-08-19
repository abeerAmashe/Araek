<?php

namespace App\Http\Controllers\subManagerController;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Room;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function getAllRooms()
    {
        $rooms = Room::select('id', 'name', 'image_url', 'description')->get();

        return response()->json([
            'rooms' => $rooms
        ], 200);
    }

    public function getAllItems()
    {
        $items = Item::select('id', 'name', 'description', 'price')->get();

        return response()->json([
            'items' => $items
        ], 200);
    }
}