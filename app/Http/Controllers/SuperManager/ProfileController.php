<?php

namespace App\Http\Controllers\supermanager;

use App\Http\Controllers\Controller;
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
}