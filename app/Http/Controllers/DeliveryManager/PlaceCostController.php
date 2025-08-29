<?php

namespace App\Http\Controllers\deliverymanager;

use App\Http\Controllers\Controller;
use App\Models\PlaceCost;
use Illuminate\Http\Request;

class PlaceCostController extends Controller
{
    public function store(Request $request)
    {
        $request->validate([
            'place' => 'required|string|max:255',
            'price' => 'required|numeric|min:0',
        ]);

        $placeCost = PlaceCost::create([
            'place' => $request->place,
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'Done ^_^',
            'data' => $placeCost
        ], 201);
    }
    public function update(Request $request, $place)
    {
        $request->validate([
            'price' => 'required|numeric|min:0',
        ]);

        $placeCost = PlaceCost::where('place', $place)->first();

        if (!$placeCost) {
            return response()->json([
                'message' => 'place not found',
            ], 404);
        }
        $placeCost->update([
            'price' => $request->price,
        ]);

        return response()->json([
            'message' => 'price updated succesfully',
            'data' => $placeCost
        ]);
    }

    public function index()
    {
        $places = PlaceCost::select('place', 'price')->get();

        return response()->json([
            'places' => $places
        ]);
    }
}
