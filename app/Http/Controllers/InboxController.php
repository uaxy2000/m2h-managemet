<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use Illuminate\Http\Request;

class InboxController extends Controller
{
    public function index(Request $request)
    {
        $userId = auth()->id();

        // Leads assigned to current user that have unread incoming WA messages
        $whatsappLeads = Lead::where('assigned_to', $userId)
            ->whereHas('activities', fn ($q) => $q
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false)
            )
            ->with(['activities' => fn ($q) => $q
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false)
                ->orderByDesc('created_at')
            ])
            ->get()
            ->map(fn ($lead) => [
                'lead'         => $lead,
                'channel'      => 'whatsapp',
                'unread_count' => $lead->activities->count(),
                'last_message' => $lead->activities->first(),
            ])
            ->sortByDesc(fn ($row) => $row['last_message']?->created_at)
            ->values();

        return view('inbox.index', compact('whatsappLeads'));
    }
}
