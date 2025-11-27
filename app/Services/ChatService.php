<?php

namespace App\Services;

use App\Models\ChatMessage;
use App\Models\ChatMessageRead;
use App\Models\ChatRoom;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class ChatService
{
    public function formatRoom(ChatRoom $room, int $forUserId): array
    {
        $productContext = $room->product ? $this->productContext($room->product) : null;
        $lastMessage = $room->lastMessage
            ? $this->formatMessage($room->lastMessage, $forUserId)
            : null;

        return [
            'room_id' => $room->id,
            'buyer' => $this->formatParticipant($room->buyer, false),
            'seller' => $this->formatParticipant($room->seller, true),
            'last_message' => $lastMessage,
            'unread_count' => $this->countUnread($room->id, $forUserId),
            'product_context' => $productContext,
        ];
    }

    public function formatParticipant(?User $user, bool $isSeller): ?array
    {
        if (! $user) {
            return null;
        }

        return [
            'user_id' => $user->id,
            'name' => $user->name,
            'avatar_url' => $this->resolveAvatarUrl($user),
            'is_seller' => $isSeller,
        ];
    }

    public function resolveAvatarUrl(User $user): ?string
    {
        $avatar = $user->avatar_url;

        if (! $avatar && env('DEV_AVATAR_PLACEHOLDER')) {
            return env('DEV_AVATAR_PLACEHOLDER');
        }

        return $avatar;
    }

    public function formatMessage(ChatMessage $message, int $forUserId): array
    {
        $read = $message->reads->firstWhere('user_id', $forUserId);

        return [
            'id' => $message->id,
            'room_id' => $message->room_id,
            'sender_id' => $message->sender_id,
            'type' => $message->type,
            'content' => $message->content,
            'payload' => $message->payload,
            'created_at' => $message->created_at,
            'read_status' => [
                'is_read' => (bool) ($read?->read_at),
                'read_at' => $read?->read_at,
            ],
        ];
    }

    public function productContext(?Product $product): ?array
    {
        if (! $product) {
            return null;
        }

        return [
            'product_id' => $product->id,
            'product_name' => $product->name,
            'product_image_url' => $product->image_url,
            'product_price' => $product->price,
            'product_stock' => $product->stock,
            'is_available' => is_null($product->stock) ? null : $product->stock > 0,
        ];
    }

    public function countUnread(int $roomId, int $userId): int
    {
        return ChatMessageRead::where('user_id', $userId)
            ->whereNull('read_at')
            ->whereHas('message', function ($query) use ($roomId) {
                $query->where('room_id', $roomId);
            })
            ->count();
    }

    public function syncReadsForParticipants(ChatMessage $message, int $senderId, int $recipientId): void
    {
        $now = now();

        $payload = [
            [
                'message_id' => $message->id,
                'user_id' => $senderId,
                'read_at' => $now,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'message_id' => $message->id,
                'user_id' => $recipientId,
                'read_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ];

        if ($senderId === $recipientId) {
            $payload = [$payload[0]];
        }

        ChatMessageRead::insert($payload);
    }

    public function markRoomAsRead(int $roomId, int $userId): void
    {
        $now = now();

        ChatMessageRead::where('user_id', $userId)
            ->whereNull('read_at')
            ->whereHas('message', function ($query) use ($roomId) {
                $query->where('room_id', $roomId);
            })
            ->update([
                'read_at' => $now,
                'updated_at' => $now,
            ]);
    }

    public function hydrateReadStatus(Collection $messages, int $userId): Collection
    {
        $messageIds = $messages->pluck('id')->all();

        $unreadIds = ChatMessageRead::where('user_id', $userId)
            ->whereNull('read_at')
            ->whereIn('message_id', $messageIds)
            ->pluck('message_id')
            ->all();

        if (empty($unreadIds)) {
            return $messages;
        }

        $readAt = now();

        ChatMessageRead::where('user_id', $userId)
            ->whereIn('message_id', $unreadIds)
            ->update([
                'read_at' => $readAt,
                'updated_at' => $readAt,
            ]);

        return $messages->map(function (ChatMessage $message) use ($userId, $unreadIds, $readAt) {
            $read = $message->reads->firstWhere('user_id', $userId);

            if ($read && in_array($message->id, $unreadIds, true)) {
                $read->read_at = $readAt;
            }

            return $message;
        });
    }
}
