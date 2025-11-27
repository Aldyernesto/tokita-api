<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Models\Product;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatRoomController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {
    }

    public function index(Request $request)
    {
        $userId = $request->user()->id;

        $rooms = ChatRoom::with([
            'buyer',
            'seller',
            'product',
            'lastMessage.reads',
            'lastMessage.sender',
        ])
            ->whereColumn('buyer_id', '!=', 'seller_id')
            ->where(function ($query) use ($userId) {
                $query->where('buyer_id', $userId)
                    ->orWhere('seller_id', $userId);
            })
            ->orderByDesc('updated_at')
            ->get()
            ->map(fn (ChatRoom $room) => $this->chatService->formatRoom($room, $userId));

        return response()->json([
            'message' => 'Daftar chat.',
            'data' => $rooms,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'seller_id' => ['required', 'exists:users,id'],
            'product_id' => ['required', 'exists:products,id'],
            'initial_message' => ['nullable', 'string'],
        ]);

        $buyerId = Auth::id();
        $sellerId = (int) $validated['seller_id'];

        if ($buyerId === $sellerId) {
            throw ValidationException::withMessages([
                'seller_id' => ['Anda tidak bisa membuat chat dengan diri sendiri.'],
            ]);
        }

        $product = Product::with('seller')->findOrFail($validated['product_id']);

        if (! $product->seller_id) {
            throw ValidationException::withMessages([
                'product_id' => ['Produk belum memiliki penjual.'],
            ]);
        }

        if ((int) $product->seller_id !== $sellerId) {
            throw ValidationException::withMessages([
                'seller_id' => ['Penjual tidak sesuai dengan pemilik produk.'],
            ]);
        }

        $existingRoom = ChatRoom::with([
            'buyer',
            'seller',
            'product',
            'lastMessage.reads',
            'lastMessage.sender',
        ])
            ->where('buyer_id', $buyerId)
            ->where('seller_id', $sellerId)
            ->where('product_id', $product->id)
            ->first();

        if ($existingRoom) {
            return response()->json([
                'message' => 'Ruang chat sudah tersedia.',
                'data' => $this->chatService->formatRoom($existingRoom, $buyerId),
            ]);
        }

        $greeting = trim((string) ($validated['initial_message'] ?? ''));

        if ($greeting === '') {
            $greeting = 'Halo, apakah produk ini tersedia?';
        }
        $productContext = $this->chatService->productContext($product);

        $room = DB::transaction(function () use ($buyerId, $sellerId, $product, $greeting, $productContext) {
            $room = ChatRoom::create([
                'buyer_id' => $buyerId,
                'seller_id' => $sellerId,
                'product_id' => $product->id,
            ]);

            $productMessage = ChatMessage::create([
                'room_id' => $room->id,
                'sender_id' => $buyerId,
                'type' => 'product_reference',
                'content' => null,
                'payload' => $productContext,
            ]);
            $this->chatService->syncReadsForParticipants($productMessage, $buyerId, $sellerId);

            $greetingMessage = ChatMessage::create([
                'room_id' => $room->id,
                'sender_id' => $buyerId,
                'type' => 'text',
                'content' => $greeting,
                'payload' => null,
            ]);
            $this->chatService->syncReadsForParticipants($greetingMessage, $buyerId, $sellerId);

            $room->last_message_id = $greetingMessage->id;
            $room->updated_at = $greetingMessage->created_at;
            $room->save();

            return $room;
        });

        $room->load([
            'buyer',
            'seller',
            'product',
            'lastMessage.reads',
            'lastMessage.sender',
        ]);

        return response()->json([
            'message' => 'Ruang chat berhasil dibuat.',
            'data' => $this->chatService->formatRoom($room, $buyerId),
        ], 201);
    }

    public function messages(Request $request, int $roomId)
    {
        $userId = $request->user()->id;
        $perPage = min(100, max(1, (int) $request->query('per_page', 50)));

        $room = ChatRoom::with(['product', 'buyer', 'seller', 'lastMessage.reads', 'lastMessage.sender'])
            ->findOrFail($roomId);

        if (! $room->isParticipant($userId)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke chat ini.',
                'data' => null,
            ], 403);
        }

        $this->chatService->markRoomAsRead($room->id, $userId);

        $messages = ChatMessage::with(['reads', 'sender'])
            ->where('room_id', $room->id)
            ->orderBy('created_at')
            ->paginate($perPage);

        $formattedMessages = collect($messages->items())
            ->map(fn (ChatMessage $message) => $this->chatService->formatMessage($message, $userId));

        return response()->json([
            'message' => 'Detail chat.',
            'data' => [
                'room' => $this->chatService->formatRoom($room->fresh([
                    'buyer',
                    'seller',
                    'product',
                    'lastMessage.reads',
                    'lastMessage.sender',
                ]), $userId),
                'messages' => $formattedMessages,
                'meta' => [
                    'current_page' => $messages->currentPage(),
                    'last_page' => $messages->lastPage(),
                    'per_page' => $messages->perPage(),
                    'total' => $messages->total(),
                ],
            ],
        ]);
    }
}
