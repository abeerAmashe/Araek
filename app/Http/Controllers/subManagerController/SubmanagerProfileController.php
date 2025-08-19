<?php

namespace App\Http\Controllers\subManagerController;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class SubmanagerProfileController extends Controller
{
    public function logoutSubManager(Request $request)
    {
        $user = $request->user();

        if (!$user || !$user->subMamager) {
            return response()->json(['error' => 'Unauthorized - Not a Sub Manager'], 403);
        }

        $user->tokens()->delete();

        return response()->json([
            'message' => 'Logged out from all sessions for Sub Manager'
        ]);
    }
}
