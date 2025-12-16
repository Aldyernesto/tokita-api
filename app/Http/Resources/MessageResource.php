<?php

namespace App\Http\Resources;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $userId = auth()->id();
        $read = $this->relationLoaded('reads') ? $this->reads->firstWhere('user_id', $userId) : null;

        return [
            'id' => $this->id,
            'message' => $this->content,
            'is_me' => $userId ? (int) $this->sender_id === (int) $userId : false,
            'time' => Carbon::parse($this->created_at)->setTimezone('Asia/Jakarta')->format('H:i'),
            'sender_id' => $this->sender_id,
            'is_read' => (bool) ($read?->read_at),
        ];
    }
}
