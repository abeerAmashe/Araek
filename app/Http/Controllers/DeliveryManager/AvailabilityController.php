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
            'availabilities' => 'required|array',
            'availabilities.*.day_of_week' => 'required|string',
            'availabilities.*.start_time' => 'required|date_format:H:i',
            'availabilities.*.end_time' => 'required|date_format:H:i|after:availabilities.*.start_time',
        ]);

        foreach ($validated['availabilities'] as $availability) {
            DeliveryCompanyAvailability::create($availability);
        }

        return response()->json(['message' => 'All availabilities created successfully']);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            'availabilities' => 'required|array',
            'availabilities.*.day_of_week' => 'required|string',
            'availabilities.*.start_time' => 'required|date_format:H:i',
            'availabilities.*.end_time' => 'required|date_format:H:i|after:availabilities.*.start_time',
        ]);

        DeliveryCompanyAvailability::truncate();

        foreach ($validated['availabilities'] as $availability) {
            DeliveryCompanyAvailability::create($availability);
        }

        return response()->json(['message' => 'All availabilities replaced successfully']);
    }
}