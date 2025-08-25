<?php

namespace App\Http\Controllers\DeliveryManager;

use App\Http\Controllers\Controller;
use App\Models\DeliveryCompanyAvailability;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
     public function store(Request $request)
    {
        $validated = $request->validate([
            'day_of_week' => 'required|string',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);

        DeliveryCompanyAvailability::create($validated);

        return response()->json(['message' => 'Done']);
    }

    public function update(Request $request, $id)
{
    $validated = $request->validate([
        'day_of_week' => 'required|string',
        'start_time' => 'required|date_format:H:i',
        'end_time' => 'required|date_format:H:i|after:start_time',
    ]);

    $availability = DeliveryCompanyAvailability::findOrFail($id);

    $availability->update($validated);

    return response()->json(['message' => 'Updated successfully']);
}

}