<?php

namespace App\Http\Controllers\Reports;

use App\Http\Controllers\Controller;
use App\Models\MeetingImportLog;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class MeetingImportController extends Controller
{
    public function index(): View
    {
        $endDate   = now();
        $startDate = $endDate->copy()->subDays(7);

        $logs       = $this->fetchLogs($startDate, $endDate);
        $nextCursor = $startDate->toISOString();
        $hasMore    = MeetingImportLog::where('created_at', '<', $startDate)->exists();

        return view('reports.meeting-imports', compact('logs', 'nextCursor', 'hasMore'));
    }

    public function more(Request $request): JsonResponse
    {
        $before    = $request->query('before') ? Carbon::parse($request->query('before')) : now();
        $endDate   = $before->copy()->subSecond();
        $startDate = $endDate->copy()->subDays(7);

        $logs    = $this->fetchLogs($startDate, $endDate);
        $hasMore = MeetingImportLog::where('created_at', '<', $startDate)->exists();

        return response()->json([
            'logs'        => $logs,
            'next_cursor' => $startDate->toISOString(),
            'has_more'    => $hasMore,
        ]);
    }

    private function fetchLogs(Carbon $startDate, Carbon $endDate): array
    {
        return MeetingImportLog::with([
            'meeting:id,start_at,end_at,is_online,online_url',
            'lead:id,first_name,last_name,email',
        ])
            ->whereBetween('created_at', [$startDate, $endDate])
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($l) => [
                'id'          => $l->id,
                'action'      => $l->action,
                'title'       => $l->title,
                'organizer'   => $l->organizer,
                'old_start'   => $l->old_start?->format('d M Y, H:i'),
                'new_start'   => $l->new_start?->format('d M Y, H:i'),
                'new_end'     => $l->new_end?->format('H:i'),
                'lead_name'   => $l->lead ? trim($l->lead->first_name . ' ' . $l->lead->last_name) : null,
                'lead_url'    => $l->lead_id ? route('leads.show', $l->lead_id) : null,
                'meeting_url' => $l->meeting_id ? route('meetings.show', $l->meeting_id) : null,
                'logged_at'   => $l->created_at->format('d M Y, H:i'),
            ])
            ->all();
    }
}
