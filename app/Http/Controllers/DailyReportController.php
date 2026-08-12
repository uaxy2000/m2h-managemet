<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Note;
use App\Models\Task;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class DailyReportController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user()->loadMissing('company');
        $isAdmin = $user->isInternalAdmin();

        abort_unless($user->isInternal(), 403);

        $date  = $request->filled('date') ? Carbon::parse($request->date)->startOfDay() : today();
        $start = $date->copy()->startOfDay();
        $end   = $date->copy()->endOfDay();

        $prevDate = $date->copy()->subDay()->toDateString();
        $nextDate = $date->copy()->addDay()->toDateString();
        $isToday  = $date->isToday();

        // Who's data to show: admin can pick any user; member always sees own
        $filterUserId = ($isAdmin && $request->filled('user_id'))
            ? $request->user_id
            : ($isAdmin ? null : $user->id);

        $users = $isAdmin
            ? User::whereIn('role', ['super_admin', 'admin', 'member'])->orderBy('name')->get()
            : collect();

        // --- New Leads ---
        $newLeads = Lead::whereBetween('created_at', [$start, $end])
            ->when(!$isAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->when($filterUserId && $isAdmin, fn ($q) => $q->where('assigned_to', $filterUserId))
            ->with(['stage', 'assignedTo', 'tags'])
            ->orderBy('created_at')
            ->get();

        // --- Notes added ---
        $notes = Note::whereBetween('created_at', [$start, $end])
            ->when(!$isAdmin, fn ($q) => $q->where('created_by', $user->id))
            ->when($filterUserId && $isAdmin, fn ($q) => $q->where('created_by', $filterUserId))
            ->whereHas('lead')
            ->with(['lead.stage', 'lead.assignedTo', 'createdBy'])
            ->orderBy('created_at')
            ->get();

        // --- Tasks created ---
        $tasksCreated = Task::whereBetween('created_at', [$start, $end])
            ->whereNotNull('lead_id')
            ->when(!$isAdmin, fn ($q) => $q->where('created_by', $user->id))
            ->when($filterUserId && $isAdmin, fn ($q) => $q->where('created_by', $filterUserId))
            ->with(['lead.stage', 'lead.assignedTo', 'createdBy', 'assignedTo'])
            ->orderBy('created_at')
            ->get()
            ->filter(fn ($t) => $t->lead !== null);

        // --- Lead Activities (WA, stage, assignment, tags, custom fields) ---
        $activities = LeadActivity::whereBetween('created_at', [$start, $end])
            ->when(!$isAdmin, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('user_id', $user->id)
                ->orWhere(fn ($i2) => $i2
                    ->where('type', 'whatsapp_incoming')
                    ->whereHas('lead', fn ($lq) => $lq->where('assigned_to', $user->id))
                )
            ))
            ->when($filterUserId && $isAdmin, fn ($q) => $q->where(fn ($inner) => $inner
                ->where('user_id', $filterUserId)
                ->orWhere(fn ($i2) => $i2
                    ->where('type', 'whatsapp_incoming')
                    ->whereHas('lead', fn ($lq) => $lq->where('assigned_to', $filterUserId))
                )
            ))
            ->whereHas('lead')
            ->with(['lead.stage', 'lead.assignedTo', 'user'])
            ->orderBy('created_at')
            ->get();

        // Unified feed (notes + tasks + activities), sorted by time, grouped by lead
        $feed = collect();
        foreach ($notes as $note) {
            $feed->push([
                'type'    => 'note',
                'sort_at' => $note->created_at,
                'lead'    => $note->lead,
                'actor'   => $note->createdBy,
                'item'    => $note,
            ]);
        }
        foreach ($tasksCreated as $task) {
            $feed->push([
                'type'    => 'task_created',
                'sort_at' => $task->created_at,
                'lead'    => $task->lead,
                'actor'   => $task->createdBy,
                'item'    => $task,
            ]);
        }
        foreach ($activities as $activity) {
            $feed->push([
                'type'    => $activity->type,
                'sort_at' => $activity->created_at,
                'lead'    => $activity->lead,
                'actor'   => $activity->user,
                'item'    => $activity,
            ]);
        }

        $feed       = $feed->sortBy('sort_at')->values();
        $feedByLead = $feed->groupBy(fn ($e) => $e['lead']?->id ?? '');

        // Summary stats
        $waIn         = $activities->where('type', 'whatsapp_incoming')->count();
        $waOut        = $activities->where('type', 'whatsapp_outgoing')->count();
        $stageChanges = $activities->where('type', 'stage_changed')->count();
        $totalLeadsTouched = $feed->pluck('lead.id')->filter()->unique()->count();

        // Stuck leads: no stage change for 7+ days
        $stuckLeads = Lead::select('leads.*', 'lh.last_changed')
            ->join(
                DB::raw('(SELECT lead_id, MAX(changed_at) as last_changed FROM lead_status_history GROUP BY lead_id) as lh'),
                'leads.id', '=', 'lh.lead_id'
            )
            ->where('lh.last_changed', '<=', now()->subDays(7))
            ->when(!$isAdmin, fn ($q) => $q->where('leads.assigned_to', $user->id))
            ->when($filterUserId && $isAdmin, fn ($q) => $q->where('leads.assigned_to', $filterUserId))
            ->with(['stage', 'assignedTo'])
            ->orderBy('lh.last_changed')
            ->limit(20)
            ->get();

        return view('reports.daily', compact(
            'date', 'prevDate', 'nextDate', 'isToday',
            'filterUserId', 'users', 'isAdmin',
            'newLeads', 'feedByLead',
            'waIn', 'waOut', 'stageChanges', 'totalLeadsTouched',
            'notes', 'tasksCreated', 'stuckLeads'
        ));
    }
}
