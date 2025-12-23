<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

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
        // Normalize numeric inputs to integers before validation
        $request->merge([
            'price' => (int) preg_replace('/[^0-9]/', '', (string) $request->input('price')),
            'stock' => (int) preg_replace('/[^0-9]/', '', (string) $request->input('stock')),
            'category_id' => (int) $request->input('category_id'),
        ]);

        $validated = $request->validate([
            'category_id' => ['required', 'exists:categories,id'],
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price' => ['required', 'numeric', 'min:0'],
            'stock' => ['required', 'numeric', 'min:0'],
            'weight' => ['nullable', 'numeric', 'min:0'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'image' => ['nullable', 'file', 'image', 'mimes:jpeg,png,jpg', 'max:2048'],
        ]);

        $user = $request->user()->load('shop');

        if (! $user->shop) {
            abort(403, 'Anda perlu membuka toko terlebih dahulu.');
        }

        $expectedShopId = $user->shop->id;
        $incomingShopRefs = [
            'shop_id' => $request->input('shop_id'),
            'store_id' => $request->input('store_id'),
            'owner_id' => $request->input('owner_id'),
        ];
        $incomingSellerId = $request->input('seller_id');

        $this->logShopContext($request, $expectedShopId, [
            'action' => 'products.store',
        ]);

        foreach ($incomingShopRefs as $key => $value) {
            if ($value !== null && $value !== '' && (int) $value !== $expectedShopId) {
                Log::warning('Product creation blocked due to mismatched shop reference.', [
                    'seller_id' => $user->id,
                    'user_shop_id' => $expectedShopId,
                    'field' => $key,
                    'value' => $value,
                ]);

                return response()->json([
                    'message' => 'Toko tidak sesuai dengan akun Anda.',
                ], 403);
            }
        }

        if ($incomingSellerId !== null && $incomingSellerId !== '' && (int) $incomingSellerId !== $expectedShopId && (int) $incomingSellerId !== $user->id) {
            Log::warning('Product creation blocked due to mismatched seller reference.', [
                'seller_id' => $user->id,
                'user_shop_id' => $expectedShopId,
                'incoming_seller_id' => $incomingSellerId,
            ]);

            return response()->json([
                'message' => 'Toko tidak sesuai dengan akun Anda.',
            ], 403);
        }

        $imagePath = $validated['image_url'] ?? null;

        if ($request->hasFile('image')) {
            $storedPath = $request->file('image')->store('uploads', 'public');
            $imagePath = $storedPath;
        }

        $product = new Product();
        $product->category_id = $validated['category_id'];
        $product->seller_id = $user->id;
        $product->shop_id = $expectedShopId;
        $product->name = $validated['name'];
        $product->description = $validated['description'] ?? null;
        $product->price = $validated['price'];
        $product->stock = $validated['stock'];
        $product->image_path = $imagePath;
        $product->save();

        $this->logShopContext($request, $expectedShopId, [
            'action' => 'products.store.enforced',
            'enforced_fields' => [
                'shop_id' => $expectedShopId,
                'store_id' => $expectedShopId,
                'owner_id' => $expectedShopId,
                'seller_id' => $user->id,
            ],
        ]);

        return response()->json([
            'message' => 'Produk berhasil dibuat.',
            'data' => $product,
        ], 201);
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
