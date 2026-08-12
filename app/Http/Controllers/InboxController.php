<?php

namespace App\Http\Controllers;

use App\Models\Lead;

class InboxController extends Controller
{
    public function index()
    {
        $user    = auth()->user();
        $isAdmin = in_array($user->role, ['super_admin', 'admin']);

        $whatsappLeads = Lead::query()
            ->when(!$isAdmin, fn ($q) => $q->where('assigned_to', $user->id))
            ->whereHas('activities', fn ($q) => $q->where('type', 'whatsapp_incoming'))
            ->withCount(['activities as unread_count' => fn ($q) => $q
                ->where('type', 'whatsapp_incoming')
                ->where('is_read', false),
            ])
            ->withMax(['activities as last_wa_at' => fn ($q) => $q
                ->where('type', 'whatsapp_incoming'),
            ], 'created_at')
            ->with(['assignedTo', 'latestWaActivity'])
            ->orderByRaw('(unread_count > 0) DESC, last_wa_at DESC')
            ->paginate(25);

        return view('inbox.index', compact('whatsappLeads'));
    }
}
