<?php

namespace App\Http\Controllers\supermanager;

use App\Http\Controllers\Controller;
use App\Models\GallaryManager;
use Illuminate\Http\Request;

class ProfileController extends Controller
{
    public function logoutGalleryManager(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->galleryManager) {
            return response()->json(['error' => 'Unauthorized - Not a Super Manager'], 403);
        }

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all sessions for Super Manager (GalleryManager)'
        ]);
    }

    public function getGallaryManagerInfo()
    {
        $user = auth()->user();

        $manager = GallaryManager::with(['user'])
            ->where('user_id', $user->id)
            ->first();

        if (!$manager) {
            return response()->json(['error' => 'Gallary Manager not found'], 404);
        }

        return response()->json([
            'full_name' => $manager->user->name,
            'phone' => $manager->user->phone ?? 'null',
            'email' => $manager->user->email,
        ]);
    }
}
