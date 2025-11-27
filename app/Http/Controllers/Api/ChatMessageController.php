<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ChatMessage;
use App\Models\ChatRoom;
use App\Services\ChatService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatMessageController extends Controller
{
    public function __construct(
        private readonly ChatService $chatService
    ) {
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'room_id' => ['required', 'exists:chat_rooms,id'],
            'message' => ['nullable', 'string'],
            'type' => ['nullable', 'in:text,product_reference'],
        ]);

        $userId = Auth::id();
        $room = ChatRoom::with(['product'])->findOrFail($validated['room_id']);

        if (! $room->isParticipant($userId)) {
            return response()->json([
                'message' => 'Anda tidak memiliki akses ke chat ini.',
                'data' => null,
            ], 403);
        }

        $recipientId = $room->otherParticipantId($userId);

        if (! $recipientId) {
            return response()->json([
                'message' => 'Penerima chat tidak ditemukan.',
                'data' => null,
            ], 422);
        }

        $type = $validated['type'] ?? 'text';

        if ($type === 'text' && blank($validated['message'] ?? null)) {
            throw ValidationException::withMessages([
                'message' => ['Pesan tidak boleh kosong.'],
            ]);
        }

        $payload = null;
        $content = $validated['message'] ?? null;

        if ($type === 'product_reference') {
            if (! $room->product) {
                throw ValidationException::withMessages([
                    'room_id' => ['Produk tidak ditemukan untuk room ini.'],
                ]);
            }

            $payload = $this->chatService->productContext($room->product);
        }

        $message = DB::transaction(function () use ($room, $userId, $recipientId, $type, $content, $payload) {
            $message = ChatMessage::create([
                'room_id' => $room->id,
                'sender_id' => $userId,
                'type' => $type,
                'content' => $content,
                'payload' => $payload,
            ]);

            $this->chatService->syncReadsForParticipants($message, $userId, $recipientId);

            $room->last_message_id = $message->id;
            $room->updated_at = $message->created_at;
            $room->save();

            return $message;
        });

        $message->load(['reads', 'sender']);

        return response()->json([
            'message' => 'Pesan berhasil dikirim.',
            'data' => $this->chatService->formatMessage($message, $userId),
        ], 201);
    }
}
