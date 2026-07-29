<?php

namespace App\Http\Controllers;

use App\Models\Lead;

class InboxController extends Controller
{
    public function index()
    {
        $user   = auth()->user();
        $isAdmin = in_array($user->role, ['super_admin', 'admin']);

        $whatsappLeads = Lead::when(!$isAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereHas('activities', fn ($q) => $q
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false)
            )
            ->with(['activities' => fn ($q) => $q
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false)
                ->orderByDesc('created_at'),
                'assignedTo',
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
