<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class ShopController extends Controller
{
    public function store(Request $request)
    {
        $user = $request->user()->load('shop');

        $this->logShopContext($request, $user->shop?->id, [
            'action' => 'shops.store',
        ]);

        if ($user->shop) {
            return response()->json([
                'message' => 'Anda sudah memiliki toko',
                'data' => $user->shop,
            ], 409);
        }

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

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('uploads', 'public');
            $validated['image_url'] = url('storage/'.$path);
        }

        $shopData = [
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'city' => $validated['city'] ?? null,
            'image_url' => $validated['image_url'] ?? null,
            'description' => $validated['description'] ?? null,
        ];

        $shop = Shop::create([
            'user_id' => $user->id,
            ...$shopData,
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

    public function products(Request $request, int $shopId)
    {
        $user = $request->user()->load('shop');

        if (! $user->shop) {
            return response()->json([
                'message' => 'Anda perlu membuka toko terlebih dahulu.',
            ], 403);
        }

        $this->logShopContext($request, $user->shop->id, [
            'action' => 'shops.products',
            'incoming_route_shop_id' => $shopId,
        ]);

        if ($user->shop->id !== $shopId) {
            Log::warning('Shop access blocked: route shop_id does not match user shop.', [
                'seller_id' => $user->id,
                'route_shop_id' => $shopId,
                'user_shop_id' => $user->shop->id,
            ]);

            return response()->json([
                'message' => 'Toko tidak sesuai dengan akun Anda.',
            ], 403);
        }

        $products = $user->shop->products()->get();

        return response()->json([
            'message' => 'Daftar produk toko.',
            'data' => [
                'shop' => $user->shop,
                'products' => $products,
            ],
        ]);
    }

    private function logShopContext(Request $request, ?int $resolvedShopId, array $context = []): void
    {
        $token = $request->bearerToken();
        $tokenHash = $token ? substr(hash('sha256', $token), 0, 12) : null;

        Log::info('Shop context', array_merge([
            'token_hash' => $tokenHash,
            'seller_id' => $request->user()?->id,
            'resolved_shop_id' => $resolvedShopId,
            'incoming_shop_id' => $request->input('shop_id'),
            'incoming_store_id' => $request->input('store_id'),
            'incoming_seller_id' => $request->input('seller_id'),
            'incoming_owner_id' => $request->input('owner_id'),
        ], $context));
    }
}
