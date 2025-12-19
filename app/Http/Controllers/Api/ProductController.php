<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = Product::query();

        if ($request->has('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        $products = $query->get();

        return response()->json($products);
    }

    public function myProducts(Request $request)
    {
        $user = $request->user()->load('shop');

        if (! $user->shop) {
            abort(403, 'Anda perlu membuka toko terlebih dahulu.');
        }

        $products = Product::where('shop_id', $user->shop->id)->get();

        return response()->json($products);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $product = Product::findOrFail($id);

        return response()->json($product);
    }

    /**
     * Store a newly created resource.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'integer', 'min:0'],
            'stock' => ['required', 'integer', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user()->load('shop');

        if (! $user->shop) {
            abort(403, 'Anda perlu membuka toko terlebih dahulu.');
        }

        $imagePath = $validated['image_url'] ?? null;

        if ($request->hasFile('image')) {
            $storedPath = $request->file('image')->store('uploads', 'public');
            $imagePath = $storedPath;
        }

        $product = new Product();
        $product->category_id = $validated['category_id'];
        $product->seller_id = auth()->id();
        $product->shop_id = auth()->user()->shop->id;
        $product->name = $validated['name'];
        $product->description = $validated['description'] ?? null;
        $product->price = $validated['price'];
        $product->stock = $validated['stock'];
        $product->image_path = $imagePath;
        $product->save();

        return response()->json([
            'message' => 'Produk berhasil dibuat.',
            'data' => $product,
        ], 201);
    }
}
