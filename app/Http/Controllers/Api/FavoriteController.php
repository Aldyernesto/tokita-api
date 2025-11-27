<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Favorite;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::with('product')
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (Favorite $favorite) => $this->formatFavorite($favorite));

        $isEmpty = $favorites->isEmpty();

        return response()->json([
            'message' => $isEmpty ? 'Belum ada barang disimpan.' : 'Daftar barang disimpan.',
            'status' => $isEmpty ? 'empty' : 'success',
            'data' => $favorites,
        ]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'exists:products,id'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validasi gagal.',
                'errors' => $validator->errors(),
                'data' => null,
            ], 422);
        }

        $userId = Auth::id();
        $productId = (int) $validator->validated()['product_id'];

        $favorite = Favorite::firstOrCreate([
            'user_id' => $userId,
            'product_id' => $productId,
        ]);

        $message = $favorite->wasRecentlyCreated
            ? 'Produk ditambahkan ke favorit.'
            : 'Produk sudah ada di favorit Anda.';

        $favorite->loadMissing('product');

        return response()->json([
            'message' => $message,
            'data' => $this->formatFavorite($favorite),
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(int $id)
    {
        $favorite = Favorite::where('user_id', Auth::id())->findOrFail($id);
        $favorite->delete();

        return response()->json([
            'message' => 'Produk dihapus dari favorit.',
            'data' => null,
        ]);
    }

    public function destroyByProduct(int $productId)
    {
        $favorite = Favorite::where('user_id', Auth::id())
            ->where('product_id', $productId)
            ->first();

        if (! $favorite) {
            return response()->json([
                'message' => 'Produk tidak ditemukan di favorit.',
                'data' => null,
            ], 404);
        }

        $favorite->delete();

        return response()->json([
            'message' => 'Produk dihapus dari favorit.',
            'data' => null,
        ]);
    }

    private function formatFavorite(Favorite $favorite): array
    {
        $product = $favorite->product;

        return [
            'id' => $favorite->id,
            'product_id' => $favorite->product_id,
            'name' => $product?->name,
            'price' => $product?->price,
            'image' => $product?->image,
        ];
    }
}
