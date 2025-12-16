<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends Controller
{
    public function updateStatus(Request $request, int $orderId)
    {
        $validated = $request->validate([
            'status' => ['required', 'string'],
        ]);

        $order = Order::with(['items.product.shop', 'user'])->findOrFail($orderId);
        $previousStatus = $order->status;
        $order->status = $validated['status'];
        $order->save();

        if ($validated['status'] === 'completed' && $previousStatus !== 'completed') {
            $this->rewardReputation($order);
        }

        return response()->json([
            'message' => 'Status transaksi diperbarui.',
            'data' => $order->fresh(['items.product.shop', 'user']),
        ]);
    }

    private function rewardReputation(Order $order): void
    {
        $buyer = $order->user;

        if ($buyer) {
            $buyer->addReputation(10);
        }

        $shops = $order->items
            ->pluck('product.shop')
            ->filter()
            ->unique('id');

        foreach ($shops as $shop) {
            $shop->addReputation(20);
        }
    }
}
