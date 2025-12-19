<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'unique:shops,slug'],
            'city' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        $user = $request->user();

        if (! $user->email_verified_at) {
            throw ValidationException::withMessages([
                'email' => ['Verifikasi email dulu bos!'],
            ]);
        }

        if ($user->shop) {
            throw ValidationException::withMessages([
                'shop' => ['Anda sudah memiliki toko.'],
            ]);
        }

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads', 'public');
            $validated['image_url'] = url('storage/'.$path);
        }

        $shop = Shop::create([
            'user_id' => $user->id,
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'city' => $validated['city'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'description' => $validated['description'] ?? null,
        ]);

        return response()->json([
            'message' => 'Toko berhasil dibuat.',
            'data' => $shop,
        ], 201);
    }

    public function show(int $id)
    {
        $shop = Shop::with('products')->findOrFail($id);

        return response()->json([
            'message' => 'Detail toko.',
            'data' => [
                'shop' => $shop,
                'products' => $shop->products,
            ],
        ]);
    }
}
