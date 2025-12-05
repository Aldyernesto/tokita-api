<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SocialAuthController extends Controller
{
    /**
     * Handle Google login from mobile app.
     */
    public function googleLogin(Request $request)
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'name' => ['required', 'string', 'max:255'],
            'google_id' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'url'],
        ]);

        $user = User::where('email', $validated['email'])->first();

        if (! $user) {
            $user = new User();
            $user->name = $validated['name'];
            $user->email = $validated['email'];
            $user->password = Hash::make(Str::random(16));
        } else {
            $user->name = $validated['name'];
        }

        if (! empty($validated['google_id'])) {
            $user->google_id = $validated['google_id'];
        }

        if (array_key_exists('avatar', $validated) && $validated['avatar']) {
            $user->avatar_url = $validated['avatar'];
        }

        $user->save();

        $token = $user->createToken('auth_token')->plainTextToken;

        return response()->json([
            'meta' => [
                'code' => 200,
                'status' => 'success',
                'message' => 'Login Success',
            ],
            'data' => [
                'access_token' => $token,
                'token_type' => 'Bearer',
                'user' => $user,
            ],
        ]);
    }
}
