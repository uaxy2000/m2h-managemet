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

        try { $this->checkTaskNotifications($user->id); } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('checkTaskNotifications failed', ['error' => $e->getMessage()]);
        }

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

        // Advance alerts: unread time-sensitive notifications created today → triggers modal
        $advanceAlerts = Notification::where('user_id', $user->id)
            ->whereIn('type', ['task_due_30min', 'task_due_5min', 'task_overdue'])
            ->whereNull('read_at')
            ->whereDate('created_at', now()->toDateString())
            ->orderByDesc('created_at')
            ->limit(10)
            ->get()
            ->map(function ($n) {
                $taskId      = ($n->meta ?? [])['task_id'] ?? null;
                $leadInfo    = null;
                $description = null;

                if ($taskId && !str_starts_with((string) $taskId, 'card_')) {
                    $task = Task::with([
                        'lead:id,first_name,last_name,pipeline_id,stage_id',
                        'lead.pipeline:id,name',
                        'lead.stage:id,name',
                    ])->find($taskId);

                    if ($task) {
                        $description = $task->description ?: null;

                        if ($task->lead) {
                            $lead     = $task->lead;
                            $leadInfo = [
                                'name'     => trim($lead->first_name . ' ' . $lead->last_name),
                                'pipeline' => $lead->pipeline?->name,
                                'stage'    => $lead->stage?->name,
                            ];
                        }
                    }
                }

                return [
                    'id'          => $n->id,
                    'type'        => $n->type,
                    'body'        => $n->body,
                    'url'         => $n->url,
                    'description' => $description,
                    'lead'        => $leadInfo,
                ];
            });

        return response()->json([
            'unread'         => $unread,
            'notifications'  => $notifications,
            'advance_alerts' => $advanceAlerts,
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
        $today   = now()->toDateString();
        $todayDt = now()->startOfDay();
        $now     = now();

        // Due today
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
                ->where('meta', 'LIKE', '%' . $task['id'] . '%')
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

        // Overdue: past due, once per task per day
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
                ->where('meta', 'LIKE', '%' . $task['id'] . '%')
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

        // 30-minute advance warning: due between now+25min and now+35min
        $w30s = $now->copy()->addMinutes(25);
        $w30e = $now->copy()->addMinutes(35);

        $due30 = Task::where('assigned_to', $userId)
            ->where('is_done', false)->whereNotNull('due_at')->whereBetween('due_at', [$w30s, $w30e])->get()
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'url' => $t->lead_id ? route('leads.show', $t->lead_id) : null])
            ->concat(
                CardTask::where('assigned_to', $userId)
                    ->where('is_done', false)->whereNotNull('due_at')->whereBetween('due_at', [$w30s, $w30e])->get()
                    ->map(fn ($t) => ['id' => 'card_' . $t->id, 'title' => $t->title, 'url' => null])
            );

        foreach ($due30 as $task) {
            $alreadySent = Notification::where('user_id', $userId)
                ->where('type', 'task_due_30min')
                ->where('meta', 'LIKE', '%' . $task['id'] . '%')
                ->exists();

            if (!$alreadySent) {
                NotificationService::send(
                    userId: $userId,
                    type:   'task_due_30min',
                    title:  'Task in 30 minutes',
                    body:   $task['title'],
                    url:    $task['url'],
                    meta:   ['task_id' => $task['id']],
                );
            }
        }

        // 5-minute advance warning: due between now+2min and now+8min
        $w5s = $now->copy()->addMinutes(2);
        $w5e = $now->copy()->addMinutes(8);

        $due5 = Task::where('assigned_to', $userId)
            ->where('is_done', false)->whereNotNull('due_at')->whereBetween('due_at', [$w5s, $w5e])->get()
            ->map(fn ($t) => ['id' => $t->id, 'title' => $t->title, 'url' => $t->lead_id ? route('leads.show', $t->lead_id) : null])
            ->concat(
                CardTask::where('assigned_to', $userId)
                    ->where('is_done', false)->whereNotNull('due_at')->whereBetween('due_at', [$w5s, $w5e])->get()
                    ->map(fn ($t) => ['id' => 'card_' . $t->id, 'title' => $t->title, 'url' => null])
            );

        foreach ($due5 as $task) {
            $alreadySent = Notification::where('user_id', $userId)
                ->where('type', 'task_due_5min')
                ->where('meta', 'LIKE', '%' . $task['id'] . '%')
                ->exists();

            if (!$alreadySent) {
                NotificationService::send(
                    userId: $userId,
                    type:   'task_due_5min',
                    title:  'Task in 5 minutes',
                    body:   $task['title'],
                    url:    $task['url'],
                    meta:   ['task_id' => $task['id']],
                );
            }
        }
    }
}
