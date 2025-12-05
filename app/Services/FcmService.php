<?php

namespace App\Services;

use App\Models\User;
use Kreait\Firebase\Messaging\CloudMessage;
use Kreait\Firebase\Messaging\Notification;
use Kreait\Laravel\Firebase\Facades\Firebase;
use Throwable;

class FcmService
{
    /**
     * Send a push notification to a user by ID.
     */
    public function sendNotification($userId, string $title, string $body): string
    {
        $user = User::find($userId);

        if (! $user) {
            return 'User not found.';
        }

        if (! $user->fcm_token) {
            return 'User does not have an FCM token.';
        }

        try {
            $message = CloudMessage::withTarget('token', $user->fcm_token)
                ->withNotification(Notification::create($title, $body));

            Firebase::messaging()->send($message);

            return 'Notification sent successfully.';
        } catch (Throwable $e) {
            return 'Failed to send notification: ' . $e->getMessage();
        }
    }
}
