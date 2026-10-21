<?php

namespace App\Http\Controllers;

use App\Models\Lead;
use App\Models\LeadActivity;
use App\Models\LeadStatusHistory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LeadStatusHistoryController extends Controller
{
    public const CONFIRM_PHRASE = 'YES REMOVE';

    public function destroy(Request $request, Lead $lead, LeadStatusHistory $entry): RedirectResponse
    {
        abort_unless($request->user()->isInternalAdmin(), 403);
        abort_unless($entry->lead_id === $lead->id, 404);

        if ($request->input('confirm') !== self::CONFIRM_PHRASE) {
            return back()->with('history_error', 'Confirmation text did not match — nothing was removed.');
        }

        $history = $lead->statusHistory()->get();
        $index   = $history->search(fn ($h) => $h->id === $entry->id);

        // The last entry represents the lead's current stage; removing it would desync leads.stage_id.
        if ($index === $history->count() - 1) {
            return back()->with('history_error', 'The current stage entry cannot be removed. Change the stage first.');
        }

        $entry->load('toStage', 'changedBy');
        $next = $history->get($index + 1);

        DB::transaction(function () use ($lead, $entry, $next, $request) {
            $next?->update([
                'from_stage_id'     => $entry->from_stage_id,
                'from_sub_stage_id' => $entry->from_sub_stage_id,
            ]);

            $entry->delete();

            LeadActivity::create([
                'lead_id'     => $lead->id,
                'user_id'     => $request->user()->id,
                'type'        => 'stage_history_removed',
                'description' => 'Stage history entry removed: ' . ($entry->toStage?->name ?? '—')
                    . ' (' . $entry->changed_at->format('d M Y H:i')
                    . ($entry->changedBy ? ', recorded by ' . $entry->changedBy->name : '') . ')',
                'visible_to'  => ['internal'],
                'created_at'  => now(),
            ]);
        });

        $flash = ['history_success' => ($entry->toStage?->name ?? 'Stage') . ' entry removed from the stage history.'];

        if ($this->paymentNowOrphaned($lead, $entry)) {
            $flash['history_warning'] = 'This lead is linked to a registration payment but no longer has a LEAD REGISTERED entry. '
                . 'Review the payment on the Payments page and correct it manually.';
        }

        return back()->with($flash);
    }

    private function paymentNowOrphaned(Lead $lead, LeadStatusHistory $entry): bool
    {
        if ($entry->to_stage_id !== PaymentsController::REGISTERED_STAGE) {
            return false;
        }

        $stillRegistered = LeadStatusHistory::where('lead_id', $lead->id)
            ->where('to_stage_id', PaymentsController::REGISTERED_STAGE)
            ->exists();

        return !$stillRegistered && DB::table('payment_leads')->where('lead_id', $lead->id)->exists();
    }
}
