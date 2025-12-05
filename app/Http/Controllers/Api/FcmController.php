<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class FcmController extends Controller
{
    public function updateToken(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'fcm_token' => ['required', 'string'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $user = $request->user();
        if (! $user) {
            return response()->json([
                'message' => 'Unauthorized',
            ], 401);
        }

        $user->fcm_token = $validator->validated()['fcm_token'];
        $user->save();

        return response()->json([
            'message' => 'FCM token updated successfully.',
            'data' => null,
        ]);
    }
}
