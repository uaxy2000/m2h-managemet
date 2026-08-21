<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\AccountTransfer;
use App\Models\FinancialAccount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccountTransferController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $transfers = AccountTransfer::with(['fromAccount', 'toAccount', 'createdBy'])
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        $accounts = FinancialAccount::with(['user', 'company'])
            ->where('is_active', true)
            ->orderByRaw("FIELD(type,'bank','cash','current_person','current_company')")
            ->orderBy('name')
            ->get();

        return view('finance.transfers.index', compact('transfers', 'accounts', 'from', 'to'));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'date'            => 'required|date',
            'from_account_id' => 'required|uuid|exists:financial_accounts,id',
            'to_account_id'   => 'required|uuid|exists:financial_accounts,id|different:from_account_id',
            'amount'          => 'required|numeric|min:0.01',
            'currency'        => 'required|in:TRY,EUR,USD,GBP',
            'description'     => 'nullable|string|max:1000',
        ]);

        $transfer = AccountTransfer::create([...$data, 'created_by' => auth()->id()]);

        // Debit the source account
        AccountMovement::create([
            'account_id'   => $transfer->from_account_id,
            'date'         => $transfer->date,
            'amount'       => -$transfer->amount,
            'description'  => $transfer->description ?? 'Transfer',
            'movable_type' => AccountTransfer::class,
            'movable_id'   => $transfer->id,
            'created_by'   => auth()->id(),
        ]);

        // Credit the destination account
        AccountMovement::create([
            'account_id'   => $transfer->to_account_id,
            'date'         => $transfer->date,
            'amount'       => $transfer->amount,
            'description'  => $transfer->description ?? 'Transfer',
            'movable_type' => AccountTransfer::class,
            'movable_id'   => $transfer->id,
            'created_by'   => auth()->id(),
        ]);

        return back()->with('success', 'Transfer recorded.');
    }

    public function destroy(AccountTransfer $transfer): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        AccountMovement::where('movable_type', AccountTransfer::class)
            ->where('movable_id', $transfer->id)
            ->delete();

        $transfer->delete();
        return back()->with('success', 'Transfer deleted.');
    }
}
