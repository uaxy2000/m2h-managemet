<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Send a notification to a user.
     * Deduplicates: won't create the same type+meta combo within 1 hour.
     */
    public static function send(
        string  $userId,
        string  $type,
        string  $title,
        ?string $body  = null,
        ?string $url   = null,
        array   $meta  = [],
    ): void {
        // Dedup: skip if an identical unread notification exists within the last hour
        try {
            $exists = Notification::where('user_id', $userId)
                ->where('type', $type)
                ->whereNull('read_at')
                ->where('created_at', '>=', now()->subHour())
                ->when(!empty($meta), function ($q) use ($meta) {
                    foreach ($meta as $k => $v) {
                        $q->whereJsonContains('meta->' . $k, $v);
                    }
                })
                ->exists();

            if ($exists) {
                return;
            }
        } catch (\Throwable) {
            // Dedup query failed (e.g. schema issue) — skip dedup, still create
        }

        try {
            Notification::create([
                'user_id' => $userId,
                'type'    => $type,
                'title'   => $title,
                'body'    => $body,
                'url'     => $url,
                'meta'    => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('NotificationService::send failed', [
                'userId' => $userId, 'type' => $type, 'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Send to multiple users at once.
     */
    public static function sendToAll(array $userIds, string $type, string $title, ?string $body = null, ?string $url = null, array $meta = []): void  // $userIds are UUID strings
    {
        foreach (array_unique($userIds) as $userId) {
            static::send($userId, $type, $title, $body, $url, $meta);
        }
    }
}
