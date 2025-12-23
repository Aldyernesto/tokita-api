<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ShopController extends Controller
{
    public function store(Request $request)
    {
        $request->merge([
            'slug' => Str::slug(($request->name ?? 'shop').'-'.time()),
        ]);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('shops', 'slug')->ignore($request->user()?->shop?->id),
            ],
            'city' => ['nullable', 'string', 'max:255'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $existingShop = $user->shop;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads', 'public');
            $validated['image_url'] = url('storage/'.$path);
        }

        $shopData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'city' => $validated['city'] ?? $existingShop?->city,
            'image_url' => $validated['image_url'] ?? $existingShop?->image_url,
            'description' => $validated['description'] ?? $existingShop?->description,
        ];

        if ($existingShop) {
            $existingShop->update($shopData);
            $shop = $existingShop->fresh();
            $statusCode = 200;
            $message = 'Toko berhasil diperbarui.';
        } else {
            $shop = Shop::create([
                'user_id' => $user->id,
                ...$shopData,
            ]);
            $statusCode = 201;
            $message = 'Toko berhasil dibuat.';
        }

        return response()->json([
            'message' => $message,
            'data' => $shop,
        ], $statusCode);
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
