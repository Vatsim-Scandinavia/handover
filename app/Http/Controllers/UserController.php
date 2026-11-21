<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Persist only the theme preference (used by the header theme toggle).
     */
    public function settings_update_theme(Request $request): JsonResponse
    {
        $data = $request->validate([
            'setting_theme' => 'required|in:light,dark,system',
        ]);

        $user = Auth::user();
        $user->setting_theme = $data['setting_theme'];
        $user->save();

        return response()->json(['setting_theme' => $user->setting_theme]);
    }
}
