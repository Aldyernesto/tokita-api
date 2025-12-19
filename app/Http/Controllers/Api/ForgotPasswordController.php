<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;

class ForgotPasswordController extends Controller
{
    public function sendResetLink(Request $request)
    {
        $payload = $this->extractPayload($request);

        $validator = Validator::make($payload, [
            'email' => ['required', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $status = Password::sendResetLink([
            'email' => $validator->validated()['email'],
        ]);

        if ($status !== Password::RESET_LINK_SENT) {
            return response()->json([
                'message' => __($status),
                'data' => null,
            ], 400);
        }

        return response()->json([
            'message' => __($status),
            'data' => null,
        ]);
    }

    private function extractPayload(Request $request): array
    {
        if ($request->isJson()) {
            return $request->json()->all();
        }

        $payload = $request->all();

        if (! empty($payload)) {
            return $payload;
        }

        $content = trim($request->getContent());

        if ($content === '') {
            return $payload;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : $payload;
    }
}
