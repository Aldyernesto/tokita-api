<?php

namespace App\Listeners;

use App\Events\OrderCreated;
use App\Services\FcmService;

class SendOrderNotification
{
    public function __construct(private readonly FcmService $fcmService)
    {
    }

    public function handle(OrderCreated $event): void
    {
        $user = $event->order->user;

        if (! $user || ! $user->fcm_token) {
            return;
        }

        $this->fcmService->sendNotification(
            $user->id,
            'Pesanan Diterima!',
            "Pesanan #{$event->order->id} kamu sedang diproses."
        );
    }
}
