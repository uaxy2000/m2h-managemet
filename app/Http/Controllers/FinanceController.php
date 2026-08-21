<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\Income;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class FinanceController extends Controller
{
    public function index(): View|RedirectResponse
    {
        $user = auth()->user();

        if ($user->isInternalMember()) {
            return redirect()->route('finance.my-account');
        }

        abort_unless($user->isInternalAdmin(), 403);

        // Account balances grouped by type
        $accounts = FinancialAccount::with(['user', 'company'])
            ->where('is_active', true)
            ->get()
            ->map(function ($a) {
                $a->current_balance = $a->balance();
                return $a;
            });

        $banks   = $accounts->where('type', 'bank');
        $cashes  = $accounts->where('type', 'cash');
        $persons = $accounts->where('type', 'current_person');
        $companies = $accounts->where('type', 'current_company');

        // Summary totals (per currency group, show TRY totals)
        $totalBankCash = $accounts->whereIn('type', ['bank', 'cash'])
            ->where('currency', 'TRY')
            ->sum('current_balance');

        $totalPersonOwed  = $persons->where('currency', 'TRY')->sum('current_balance');
        $totalCompanyOwed = $companies->where('currency', 'TRY')->sum('current_balance');

        // Recent activity
        $recentExpenses = Expense::with(['category', 'paidBy', 'createdBy'])
            ->orderByDesc('date')->orderByDesc('created_at')
            ->limit(5)->get();

        $recentIncomes = Income::with(['category', 'company', 'createdBy'])
            ->orderByDesc('date')->orderByDesc('created_at')
            ->limit(5)->get();

        // This month totals
        $monthStart = now()->startOfMonth()->toDateString();
        $today      = now()->toDateString();

        $monthExpenses = Expense::whereBetween('date', [$monthStart, $today])
            ->where('currency', 'TRY')->sum('amount');

        $monthIncomes = Income::whereBetween('date', [$monthStart, $today])
            ->where('currency', 'TRY')->sum('amount');

        // Expense breakdown by category (this month)
        $expenseByCategory = Expense::whereBetween('date', [$monthStart, $today])
            ->where('currency', 'TRY')
            ->join('transaction_categories', 'transaction_categories.id', '=', 'expenses.category_id')
            ->selectRaw('transaction_categories.name as category_name, SUM(expenses.amount) as total')
            ->groupBy('transaction_categories.name')
            ->orderByRaw('SUM(expenses.amount) DESC')
            ->get();

        return view('finance.index', compact(
            'banks', 'cashes', 'persons', 'companies',
            'totalBankCash', 'totalPersonOwed', 'totalCompanyOwed',
            'recentExpenses', 'recentIncomes',
            'monthExpenses', 'monthIncomes', 'expenseByCategory'
        ));
    }

    public function myAccount(): View
    {
        $user = auth()->user();
        abort_unless($user->isInternalMember() || $user->isInternalAdmin(), 403);

        $myAccount = FinancialAccount::where('user_id', $user->id)->first();
        $myBalance = $myAccount ? $myAccount->balance() : 0;

        $myExpenses = Expense::with(['category.parent', 'createdBy'])
            ->where('paid_by_user_id', $user->id)
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $expenseGroups = TransactionCategory::forExpenses()->with('children')->get();

        return view('finance.my_account', compact('myAccount', 'myBalance', 'myExpenses', 'expenseGroups'));
    }
}
