<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExpenseController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $from = $request->get('from', now()->startOfMonth()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $query = Expense::with(['category.parent', 'paidBy', 'sourceAccount', 'lead', 'createdBy'])
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }

        $expenses   = $query->get();
        $categories = TransactionCategory::forExpenses()->with('children')->get();
        $accounts   = FinancialAccount::whereIn('type', ['bank', 'cash'])->where('is_active', true)->orderBy('name')->get();
        $internalUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))->orderBy('name')->get();

        $totalsByCurrency = $expenses->groupBy('currency')->map(fn ($g) => $g->sum('amount'));

        return view('finance.expenses.index', compact(
            'expenses', 'categories', 'accounts', 'internalUsers',
            'from', 'to', 'totalsByCurrency'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'date'              => 'required|date',
            'category_id'       => 'required|uuid|exists:transaction_categories,id',
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'required|in:TRY,EUR,USD,GBP',
            'description'       => 'nullable|string|max:1000',
            'lead_id'           => 'nullable|uuid|exists:leads,id',
            'paid_by_user_id'   => 'nullable|uuid|exists:users,id',
            'source_account_id' => 'nullable|uuid|exists:financial_accounts,id',
        ]);

        $expense = Expense::create([...$data, 'created_by' => auth()->id()]);

        // If paid by a person → credit their current account (company owes them)
        if ($expense->paid_by_user_id) {
            $user    = User::find($expense->paid_by_user_id);
            $account = FinancialAccount::forUser($user, $expense->currency);

            AccountMovement::create([
                'account_id'   => $account->id,
                'date'         => $expense->date,
                'amount'       => $expense->amount,
                'description'  => $expense->description ?? ($expense->category->name ?? 'Expense'),
                'movable_type' => Expense::class,
                'movable_id'   => $expense->id,
                'created_by'   => auth()->id(),
            ]);
        }

        // If paid directly from source account → debit it
        if ($expense->source_account_id) {
            AccountMovement::create([
                'account_id'   => $expense->source_account_id,
                'date'         => $expense->date,
                'amount'       => -$expense->amount,
                'description'  => $expense->description ?? ($expense->category->name ?? 'Expense'),
                'movable_type' => Expense::class,
                'movable_id'   => $expense->id,
                'created_by'   => auth()->id(),
            ]);
        }

        return back()->with('success', 'Expense recorded.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        // Remove related movements
        AccountMovement::where('movable_type', Expense::class)
            ->where('movable_id', $expense->id)
            ->delete();

        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }
}
