<?php

namespace App\Http\Controllers\GallaryManager;

use App\Http\Controllers\Controller;
use App\Models\SubManager;
use Illuminate\Http\Request;

class SubManagerController extends Controller
{
    public function show($id)
{
    $subManager = SubManager::with(['user', 'branch'])->find($id);

    if (!$subManager || !$subManager->user) {
        return response()->json(['error' => 'Sub Manager not found'], 404);
    }

    return response()->json([
        'name' => $subManager->user->name,
        'phone' => $subManager->user->phone ?? 'null',
        'email' => $subManager->user->email,
        'branch' => [
            'address' => $subManager->branch->address ?? 'not found',
            'latitude' => $subManager->branch->latitude ?? null,
            'longitude' => $subManager->branch->longitude ?? null,
        ]
    ]);
}
}