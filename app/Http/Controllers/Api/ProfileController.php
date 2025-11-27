<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Validator as ValidationValidator;

class ProfileController extends Controller
{
    public function update(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,' . $request->user()->id],
            'phone' => ['nullable', 'string', 'max:20'],
            'avatar_url' => ['nullable', 'url'],
            'avatar' => ['nullable', 'image', 'max:2048'],
            'remove_avatar' => ['nullable', 'boolean'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = $request->user();
        $validated = $validator->validated();

        if ($request->hasFile('avatar')) {
            $this->deleteStoredAvatar($user->avatar_url);
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar_url'] = Storage::disk('public')->url($path);
        } elseif (! empty($validated['remove_avatar'])) {
            $this->deleteStoredAvatar($user->avatar_url);
            $validated['avatar_url'] = null;
        }

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
        ]);

        if (array_key_exists('avatar_url', $validated)) {
            $user->avatar_url = $validated['avatar_url'];
        }

        $user->save();

        return response()->json([
            'message' => 'Profil berhasil diperbarui.',
            'data' => $this->formatUser($user->fresh()),
        ]);
    }

    public function updatePassword(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'current_password' => ['required', 'string'],
            'new_password' => ['required', 'string', 'min:8'],
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $validated = $validator->validated();
        $user = $request->user();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => [
                    'current_password' => ['Password saat ini tidak sesuai.'],
                ],
                'data' => null,
            ], 422);
        }

        $user->password = Hash::make($validated['new_password']);
        $user->save();

        return response()->json([
            'message' => 'Password berhasil diperbarui.',
            'data' => null,
        ]);
    }

    public function destroy(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'confirmation' => ['required', 'in:DELETE'],
        ], [
            'confirmation.in' => 'Ketik DELETE untuk konfirmasi penghapusan akun.',
        ]);

        if ($validator->fails()) {
            return $this->validationErrorResponse($validator);
        }

        $user = $request->user();
        $user->tokens()->delete();
        $user->delete();

        return response()->json([
            'message' => 'Akun berhasil dihapus.',
            'data' => null,
        ]);
    }

    private function formatUser(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'avatar_url' => $user->avatar_url,
        ];
    }

    private function deleteStoredAvatar(?string $avatarUrl): void
    {
        if (! $avatarUrl) {
            return;
        }

        $disk = Storage::disk('public');
        $storageBaseUrl = rtrim($disk->url('/'), '/');

        if (! str_starts_with($avatarUrl, $storageBaseUrl)) {
            return;
        }

        $relativePath = ltrim(str_replace($storageBaseUrl, '', $avatarUrl), '/');
        $disk->delete($relativePath);
    }

    private function validationErrorResponse(ValidationValidator $validator)
    {
        return response()->json([
            'message' => 'Validasi gagal.',
            'errors' => $validator->errors(),
            'data' => null,
        ], 422);
    }
}
