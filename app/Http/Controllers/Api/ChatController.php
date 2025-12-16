<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\MessageResource;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Product;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {
    }

    public function start(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => ['required', 'exists:users,id'],
            'product_id' => ['nullable', 'exists:products,id'],
        ]);

        $buyerId = (int) $request->user()->id;
        $sellerId = (int) $validated['seller_id'];
        $productId = isset($validated['product_id']) ? (int) $validated['product_id'] : null;

        if ($buyerId === $sellerId) {
            throw ValidationException::withMessages([
                'seller_id' => ['Anda tidak bisa memulai chat dengan diri sendiri.'],
            ]);
        }

        $product = null;

        if ($productId) {
            $product = Product::with('seller')->findOrFail($productId);

            if ((int) $product->seller_id !== $sellerId) {
                throw ValidationException::withMessages([
                    'product_id' => ['Produk tidak dimiliki oleh penjual yang dipilih.'],
                ]);
            }
        }

        $room = ChatRoom::with(['buyer', 'seller', 'product', 'lastMessage.reads', 'lastMessage.sender'])
            ->where('buyer_id', $buyerId)
            ->where('seller_id', $sellerId)
            ->when($productId, fn ($query) => $query->where('product_id', $productId))
            ->first();

        $contextMessage = null;

        if (! $room) {
            if (! $productId) {
                throw ValidationException::withMessages([
                    'product_id' => ['product_id wajib diisi untuk membuat chat baru.'],
                ]);
            }

            $room = ChatRoom::create([
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'product_id' => $productId,
            ]);
        }

        if ($productId && $product) {
            $contextMessage = DB::transaction(function () use ($room, $product, $buyerId) {
                $recipientId = $room->otherParticipantId($buyerId);

                if (! $recipientId) {
                    throw ValidationException::withMessages([
                        'seller_id' => ['Peserta chat tidak valid.'],
                    ]);
                }

                $message = ChatMessage::create([
                    'room_id' => $room->id,
                    'sender_id' => $buyerId,
                    'type' => 'text',
                    'content' => null,
                    'payload' => $this->chatService->productContext($product),
                    'attachment_type' => 'product',
                    'attachment_id' => $product->id,
                ]);

                $this->chatService->syncReadsForParticipants($message, $buyerId, $recipientId);

                $room->last_message_id = $message->id;
                $room->updated_at = $message->created_at;
                $room->save();

                return $message;
            });
        }

        $room->load([
            'buyer',
            'seller',
            'product',
            'lastMessage.reads',
            'lastMessage.sender',
        ]);

        return response()->json([
            'message' => 'Chat berhasil dimulai.',
            'data' => [
                'room' => $this->chatService->formatRoom($room, $buyerId),
                'context_message' => $contextMessage
                    ? new MessageResource($contextMessage->load(['reads', 'sender']))
                    : null,
            ],
        ], $contextMessage ? 201 : 200);
    }
}
