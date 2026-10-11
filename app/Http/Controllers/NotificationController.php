<?php

namespace App\Http\Controllers;

use App\Models\CardTask;
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

    private function checkTaskNotifications(string $userId): void
    {
        $today    = now()->toDateString();
        $todayDt  = now()->startOfDay();

        // Collect lead tasks + board tasks due today
        $dueTasks = Task::where('assigned_to', $userId)
            ->where('is_done', false)->whereNotNull('due_at')->whereDate('due_at', $today)->get()
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'url' => $t->lead_id ? route('leads.show', $t->lead_id) : null])
            ->concat(
                CardTask::where('assigned_to', $userId)
                    ->where('is_done', false)->whereNotNull('due_at')->whereDate('due_at', $today)->get()
                    ->map(fn ($t) => ['id' => 'card_' . $t->id, 'title' => $t->title, 'url' => null])
            );

        foreach ($dueTasks as $task) {
            $alreadyToday = Notification::where('user_id', $userId)
                ->where('type', 'task_due')
                ->whereDate('created_at', $today)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.task_id')) = ?", [$task['id']])
                ->exists();

            if (!$alreadyToday) {
                NotificationService::send(
                    userId: $userId,
                    type:   'task_due',
                    title:  'Task due today',
                    body:   $task['title'],
                    url:    $task['url'],
                    meta:   ['task_id' => $task['id']],
                );
            }
        }

        // Overdue: lead tasks + board tasks past due, not yet notified today
        $overdue = Task::where('assigned_to', $userId)
            ->where('is_done', false)->whereNotNull('due_at')->where('due_at', '<', $todayDt)->get()
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'url' => $t->lead_id ? route('leads.show', $t->lead_id) : null])
            ->concat(
                CardTask::where('assigned_to', $userId)
                    ->where('is_done', false)->whereNotNull('due_at')->where('due_at', '<', $todayDt)->get()
                    ->map(fn ($t) => ['id' => 'card_' . $t->id, 'title' => $t->title, 'url' => null])
            );

        foreach ($overdue as $task) {
            $alreadyToday = Notification::where('user_id', $userId)
                ->where('type', 'task_overdue')
                ->whereDate('created_at', $today)
                ->whereRaw("JSON_UNQUOTE(JSON_EXTRACT(meta, '$.task_id')) = ?", [$task['id']])
                ->exists();

            if (!$alreadyToday) {
                NotificationService::send(
                    userId: $userId,
                    type:   'task_overdue',
                    title:  'Overdue task',
                    body:   $task['title'],
                    url:    $task['url'],
                    meta:   ['task_id' => $task['id']],
                );
            }
        }
    }
}
