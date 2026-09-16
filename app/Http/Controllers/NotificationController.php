<?php

namespace App\Http\Controllers;

use App\Models\Notification;
use App\Models\Task;
use App\Services\NotificationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * Poll endpoint — returns unread count + recent notifications.
     * Also checks for due/overdue tasks and creates notifications on the fly.
     */
    public function poll(): JsonResponse
    {
        $user = auth()->user();

        $this->checkTaskNotifications($user->id);

        $notifications = Notification::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->limit(20)
            ->get()
            ->map(fn($n) => [
                'id'         => $n->id,
                'type'       => $n->type,
                'title'      => $n->title,
                'body'       => $n->body,
                'url'        => $n->url,
                'read'       => $n->read_at !== null,
                'created_at' => $n->created_at->diffForHumans(),
            ]);

        $unread = $notifications->where('read', false)->count();

        return response()->json([
            'unread'        => $unread,
            'notifications' => $notifications,
        ]);
    }

    /** Mark a single notification as read. */
    public function markRead(Notification $notification): JsonResponse
    {
        abort_unless($notification->user_id === auth()->id(), 403);
        $notification->markRead();
        return response()->json(['ok' => true]);
    }

    /** Mark all notifications as read. */
    public function markAllRead(): JsonResponse
    {
        Notification::where('user_id', auth()->id())
            ->whereNull('read_at')
            ->update(['read_at' => now()]);

        return response()->json(['ok' => true]);
    }

    // ─── Task due / overdue ───────────────────────────────────────────────────

    private function checkTaskNotifications(int $userId): void
    {
        $today    = now()->toDateString();
        $todayDt  = now()->startOfDay();

        // Tasks due today (time == 00:00 means board task → just date)
        $dueTasks = Task::where('assigned_to', $userId)
            ->where('is_done', false)
            ->whereNotNull('due_at')
            ->whereDate('due_at', $today)
            ->get();

        foreach ($dueTasks as $task) {
            NotificationService::send(
                userId: $userId,
                type:   'task_due',
                title:  'Task due today',
                body:   $task->title,
                url:    $task->lead_id ? route('leads.show', $task->lead_id) : null,
                meta:   ['task_id' => $task->id],
            );
        }

        // Overdue tasks (due_at < today, not completed, not yet notified today)
        $overdue = Task::where('assigned_to', $userId)
            ->where('is_done', false)
            ->whereNotNull('due_at')
            ->where('due_at', '<', $todayDt)
            ->get();

        foreach ($overdue as $task) {
            // Only notify once per day per overdue task
            $alreadyToday = Notification::where('user_id', $userId)
                ->where('type', 'task_overdue')
                ->whereDate('created_at', $today)
                ->whereJsonContains('meta->task_id', $task->id)
                ->exists();

            if (!$alreadyToday) {
                NotificationService::send(
                    userId: $userId,
                    type:   'task_overdue',
                    title:  'Overdue task',
                    body:   $task->title,
                    url:    $task->lead_id ? route('leads.show', $task->lead_id) : null,
                    meta:   ['task_id' => $task->id],
                );
            }
        }
    }
}
