<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\FinancialAccount;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentsController extends Controller
{
    const REGISTERED_STAGE = 'a2663266-e3b6-42cd-b998-a479287c5256';

    public function index(): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $internalUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->orderBy('name')
            ->get();

        $userIds = $internalUsers->pluck('id');

        // Total registered per user (first time each lead reached REGISTERED)
        $totalRegistered = DB::table('lead_status_history')
            ->join('leads', 'leads.id', '=', 'lead_status_history.lead_id')
            ->where('lead_status_history.to_stage_id', self::REGISTERED_STAGE)
            ->whereIn('leads.assigned_to', $userIds)
            ->select('leads.assigned_to', DB::raw('COUNT(DISTINCT lead_status_history.lead_id) as cnt'))
            ->groupBy('leads.assigned_to')
            ->pluck('cnt', 'assigned_to');

        // Paid lead count per user
        $paidCounts = DB::table('payment_leads')
            ->join('payments', 'payments.id', '=', 'payment_leads.payment_id')
            ->whereIn('payments.user_id', $userIds)
            ->select('payments.user_id', DB::raw('COUNT(*) as cnt'))
            ->groupBy('payments.user_id')
            ->pluck('cnt', 'user_id');

        // Last payment per user
        $lastPayments = Payment::whereIn('user_id', $userIds)
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get()
            ->unique('user_id')
            ->keyBy('user_id');

        $userStats = $internalUsers->map(function ($u) use ($totalRegistered, $paidCounts, $lastPayments) {
            $total      = (int) ($totalRegistered[$u->id] ?? 0);
            $paid       = (int) ($paidCounts[$u->id]     ?? 0);
            $unpaid     = max(0, $total - $paid);
            $last       = $lastPayments[$u->id] ?? null;
            $unitPrice  = $last ? $last->unitPrice() : null;

            return [
                'user'              => $u,
                'total'             => $total,
                'paid'              => $paid,
                'unpaid'            => $unpaid,
                'last_payment'      => $last,
                'unit_price'        => $unitPrice,
                'estimated_pending' => ($unitPrice && $unpaid > 0)
                    ? round($unitPrice * $unpaid, 2)
                    : null,
            ];
        })->filter(fn ($s) => $s['total'] > 0)->values();

        $payments = Payment::with(['user', 'createdBy'])
            ->orderByDesc('paid_at')
            ->orderByDesc('created_at')
            ->get();

        return view('payments.index', compact('userStats', 'payments', 'internalUsers'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'user_id'    => 'required|uuid|exists:users,id',
            'amount'     => 'required|numeric|min:0.01',
            'currency'   => 'required|in:TRY,EUR,USD',
            'lead_count' => 'required|integer|min:1',
            'paid_at'    => 'required|date',
            'note'       => 'nullable|string|max:500',
            'document'   => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $payment = Payment::create([
            ...$data,
            'created_by' => auth()->id(),
        ]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->storeAs(
                'finance-docs/payments',
                $payment->id . '.' . $file->getClientOriginalExtension(),
                'local'
            );
            $payment->update(['document_path' => $path]);
        }

        // En eski N ödenmemiş registered lead'i bul
        $leadIds = DB::table('lead_status_history as lsh')
            ->join('leads', 'leads.id', '=', 'lsh.lead_id')
            ->where('leads.assigned_to', $data['user_id'])
            ->where('lsh.to_stage_id', self::REGISTERED_STAGE)
            ->whereNotIn('lsh.lead_id', DB::table('payment_leads')->select('lead_id'))
            ->groupBy('lsh.lead_id')
            ->select('lsh.lead_id', DB::raw('MIN(lsh.changed_at) as first_registered_at'))
            ->orderBy('first_registered_at')
            ->limit((int) $data['lead_count'])
            ->pluck('lead_id');

        if ($leadIds->isNotEmpty()) {
            DB::table('payment_leads')->insert(
                $leadIds->map(fn ($id) => [
                    'id'         => (string) Str::uuid(),
                    'payment_id' => $payment->id,
                    'lead_id'    => $id,
                ])->toArray()
            );
        }

        $linked = $leadIds->count();
        $msg = "Ödeme kaydedildi. {$linked} lead ödendi olarak işaretlendi.";
        if ($linked < (int) $data['lead_count']) {
            $msg .= " (Ödenmemiş lead sayısı {$data['lead_count']}'den az: yalnızca {$linked} lead bulundu.)";
        }

        // Cari entegrasyonu: agent'ın current_person hesabına hakediş + ödeme
        try {
            $agent   = User::find($data['user_id']);
            $account = FinancialAccount::forUser($agent, $data['currency']);
            $desc    = "{$linked} lead için";

            AccountMovement::create([
                'account_id'   => $account->id,
                'date'         => $data['paid_at'],
                'amount'       => $data['amount'],
                'description'  => $desc . ' hakediş',
                'movable_type' => Payment::class,
                'movable_id'   => $payment->id,
                'created_by'   => auth()->id(),
            ]);

            AccountMovement::create([
                'account_id'   => $account->id,
                'date'         => $data['paid_at'],
                'amount'       => -$data['amount'],
                'description'  => $desc . ' ödeme',
                'movable_type' => Payment::class,
                'movable_id'   => $payment->id,
                'created_by'   => auth()->id(),
            ]);
        } catch (\Throwable) {
            // Finance tables may not exist yet; silently skip
        }

        return back()->with('success', $msg);
    }

    public function destroy(Payment $payment): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        $payment->delete(); // payment_leads cascade ile silinir
        return back()->with('success', 'Ödeme kaydı silindi, ilgili leadler tekrar ödenmemiş sayılır.');
    }
}
