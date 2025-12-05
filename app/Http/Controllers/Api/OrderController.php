<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Services\RegionResolver;
use App\Events\OrderCreated;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    public function __construct(
        private readonly RegionResolver $regionResolver
    ) {
    }

    public function checkout(Request $request)
    {
        $validated = $request->validate([
            'address_id' => ['required', 'exists:addresses,id'],
            'shipping_courier_name' => ['required', 'string', 'max:255'],
            'shipping_cost' => ['required', 'integer', 'min:0'],
            'payment_method' => ['required', 'string', 'max:255'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.product_id' => ['required', 'exists:products,id'],
            'items.*.quantity' => ['required', 'integer', 'min:1'],
        ]);

        $userId = Auth::id();
        $address = Address::where('id', $validated['address_id'])
            ->where('user_id', $userId)
            ->first();

        if (! $address) {
            throw ValidationException::withMessages([
                'address_id' => ['Alamat tidak ditemukan.'],
            ]);
        }

        try {
            $order = DB::transaction(function () use ($validated, $userId) {
                $subtotal = 0;
                $orderItemsPayload = [];

                foreach ($validated['items'] as $item) {
                    $product = Product::lockForUpdate()->find($item['product_id']);

                    if (! $product) {
                        throw ValidationException::withMessages([
                            'items' => ['Product not found.'],
                        ]);
                    }

                    if ($product->stock < $item['quantity']) {
                        throw ValidationException::withMessages([
                            'items' => ["Stock for {$product->name} is insufficient."],
                        ]);
                    }

                    $subtotal += $product->price * $item['quantity'];

                    $orderItemsPayload[] = [
                        'product' => $product,
                        'quantity' => $item['quantity'],
                        'price' => $product->price,
                    ];
                }

                $totalPrice = $subtotal + $validated['shipping_cost'];
                $paymentMethod = $validated['payment_method'];

                $order = new Order();
                $order->order_number = $this->generateOrderNumber();
                $order->user_id = $userId;
                $order->address_id = $validated['address_id'];
                $order->shipping_courier_name = $validated['shipping_courier_name'];
                $order->shipping_cost = $validated['shipping_cost'];
                $order->payment_method = $paymentMethod;
                $order->total_price = $totalPrice;
                $order->payment_status = 'menunggu_pembayaran';
                $order->status = $paymentMethod === 'COD'
                    ? 'diproses'
                    : 'menunggu_pembayaran';
                $order->save();

                foreach ($orderItemsPayload as $itemPayload) {
                    OrderItem::create([
                        'order_id' => $order->id,
                        'product_id' => $itemPayload['product']->id,
                        'quantity' => $itemPayload['quantity'],
                        'price' => $itemPayload['price'],
                    ]);

                    $itemPayload['product']->decrement('stock', $itemPayload['quantity']);
                }

                return $order->load('items.product', 'address');
            });

            event(new OrderCreated($order));

            return response()->json($this->appendShippingAddress($order), 201);
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'message' => 'Failed to process checkout.',
            ], 500);
        }
    }

    public function index()
    {
        $orders = Order::where('user_id', Auth::id())
            ->with(['items.product', 'address'])
            ->latest()
            ->get()
            ->map(fn (Order $order) => $this->appendShippingAddress($order));

        return response()->json($orders);
    }

    public function show($id)
    {
        $order = Order::with(['items.product', 'address'])
            ->where('user_id', Auth::id())
            ->findOrFail($id);

        return response()->json($this->appendShippingAddress($order));
    }

    private function appendShippingAddress(Order $order): Order
    {
        $order->setAttribute('shipping_address', $order->address
            ? $this->formatShippingAddress($order->address)
            : null);

        return $order;
    }

    private function formatShippingAddress(Address $address): array
    {
        $region = $address->village_id
            ? $this->regionResolver->resolve((string) $address->village_id)
            : [];

        $city = $address->city ?: ($region['regency_name'] ?? null);
        $province = $address->province ?: ($region['province_name'] ?? null);
        $addressLine = $address->address_line ?: $this->buildAddressLine([
            'street_name' => $address->street_name,
            'rt' => $address->rt,
            'rw' => $address->rw,
        ], $region);

        return [
            'id' => $address->id,
            'label' => $address->label,
            'recipient_name' => $address->recipient_name,
            'phone_number' => $address->phone_number,
            'phone' => $address->phone_number,
            'address_line' => $addressLine,
            'street_name' => $address->street_name,
            'rt' => $address->rt,
            'rw' => $address->rw,
            'village_id' => $address->village_id,
            'village_name' => $region['village_name'] ?? null,
            'district_name' => $region['district_name'] ?? null,
            'regency_name' => $region['regency_name'] ?? null,
            'province_name' => $region['province_name'] ?? null,
            'city' => $city,
            'province' => $province,
            'postal_code' => $address->postal_code,
            'latitude' => $address->latitude,
            'longitude' => $address->longitude,
            'is_default' => (bool) $address->is_default,
            'is_primary' => (bool) $address->is_default,
        ];
    }

    private function buildAddressLine(array $address, array $region): ?string
    {
        $parts = array_filter([
            $address['street_name'] ?? null,
            $this->formatRtRw($address['rt'] ?? null, $address['rw'] ?? null),
            $region['village_name'] ?? null,
            $region['district_name'] ?? null,
            $region['regency_name'] ?? null,
            $region['province_name'] ?? null,
        ]);

        return $parts ? implode(', ', $parts) : null;
    }

    private function formatRtRw(?string $rt, ?string $rw): ?string
    {
        if ($rt && $rw) {
            return sprintf('RT %s / RW %s', $rt, $rw);
        }

        if ($rt) {
            return sprintf('RT %s', $rt);
        }

        if ($rw) {
            return sprintf('RW %s', $rw);
        }

        return null;
    }

    private function generateOrderNumber(): string
    {
        do {
            $number = sprintf(
                'TOK-%s-%s',
                now()->format('Ymd'),
                strtoupper(Str::random(6))
            );
        } while (Order::where('order_number', $number)->exists());

        return $number;
    }
}
