<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class EmailReportController extends Controller
{
    public function index(Request $request)
    {
        $user    = auth()->user()->loadMissing('company');
        $isAdmin = $user->isInternalAdmin();

        abort_unless($user->isInternal(), 403);

        // Date range — default: last 30 days
        $from = $request->filled('from') ? Carbon::parse($request->from)->startOfDay() : today()->subDays(29)->startOfDay();
        $to   = $request->filled('to')   ? Carbon::parse($request->to)->endOfDay()     : today()->endOfDay();

        // Available accounts for filter
        $sharedAccount = Setting::get('imap_username'); // info@...
        $userAccounts  = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->where('imap_enabled', true)
            ->whereNotNull('imap_user')
            ->orderBy('name')
            ->get(['id', 'name', 'imap_user']);

        // Selected accounts (multi)
        $selectedAccounts = $request->filled('accounts') ? (array) $request->accounts : [];

        // Lead search
        $leadSearch = trim($request->input('lead', ''));

        // Base query: only IMAP-synced emails
        $query = LeadActivity::whereIn('type', ['email_in', 'email_out'])
            ->whereNotNull('imap_message_id')
            ->whereBetween('created_at', [$from, $to])
            ->with(['lead.assignedTo']);

        // Non-admin: only leads assigned to this user
        if (!$isAdmin) {
            $query->whereHas('lead', fn ($q) => $q->where('assigned_to', $user->id));
        }

        // Account filter (synced_from in meta)
        if (!empty($selectedAccounts)) {
            $query->where(function ($q) use ($selectedAccounts) {
                foreach ($selectedAccounts as $acc) {
                    $q->orWhere('meta', 'like', '%' . addslashes($acc) . '%');
                }
            });
        }

        // Lead name / email search
        if ($leadSearch !== '') {
            $query->whereHas('lead', fn ($q) => $q
                ->where('name', 'like', "%{$leadSearch}%")
                ->orWhere('email', 'like', "%{$leadSearch}%")
            );
        }

        $emails = $query->orderByDesc('created_at')->paginate(50)->withQueryString();

        // Summary counts
        $totalIn  = (clone $query)->where('type', 'email_in')->count();
        $totalOut = (clone $query)->where('type', 'email_out')->count();

        return view('reports.emails', compact(
            'emails', 'from', 'to',
            'sharedAccount', 'userAccounts', 'selectedAccounts',
            'leadSearch', 'isAdmin',
            'totalIn', 'totalOut',
        ));
    }
}
