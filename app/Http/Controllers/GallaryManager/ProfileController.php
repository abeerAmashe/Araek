<?php

namespace App\Http\Controllers\GallaryManager;

use App\Http\Controllers\Controller;
use App\Models\GallaryManager;
use Illuminate\Http\Request;

class ProfileController extends Controller
{

    public function getGallaryManagerInfo()
    {
        $user = auth()->user();

        // نجيب المدير المرتبط بهذا المستخدم
        $manager = GallaryManager::with(['user', 'branch'])
            ->where('user_id', $user->id)
            ->first();

        if (!$manager) {
            return response()->json(['error' => 'Gallary Manager not found'], 404);
        }

        return response()->json([
            'full_name' => $manager->user->name,
            'phone' => $manager->user->phone ?? 'null',
            'email' => $manager->user->email,
            'branch_address' => optional($manager->branch->first())->address ?? 'not found',
        ]);
    }
    
}