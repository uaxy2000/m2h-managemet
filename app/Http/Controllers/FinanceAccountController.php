<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\Company;
use App\Models\FinancialAccount;
use App\Models\TransactionCategory;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FinanceAccountController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $accounts = FinancialAccount::with(['user', 'company'])
            ->orderByRaw("FIELD(type,'bank','cash','current_person','current_company')")
            ->orderBy('name')
            ->get()
            ->map(function ($a) {
                $a->current_balance = $a->balance();
                return $a;
            });

        $internalUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))
            ->orderBy('name')->get();

        $serviceProviders = Company::where('type', 'service_provider')->orderBy('name')->get();

        // Groups with their children for the category management UI
        $expenseGroups = TransactionCategory::forExpenses()->with('children')->get();
        $incomeGroups  = TransactionCategory::forIncomes()->with('children')->get();

        return view('finance.accounts.index', compact(
            'accounts', 'internalUsers', 'serviceProviders',
            'expenseGroups', 'incomeGroups'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'name'       => 'required|string|max:100',
            'type'       => 'required|in:bank,cash,current_person,current_company',
            'currency'   => 'required|in:TRY,EUR,USD,GBP',
            'user_id'    => 'nullable|uuid|exists:users,id',
            'company_id' => 'nullable|uuid|exists:companies,id',
        ]);

        FinancialAccount::create($data);

        return back()->with('success', 'Account created.');
    }

    public function update(Request $request, FinancialAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'currency'  => 'required|in:TRY,EUR,USD,GBP',
            'is_active' => 'boolean',
        ]);

        $account->update(['name' => $data['name'], 'currency' => $data['currency'], 'is_active' => $request->boolean('is_active', true)]);

        return back()->with('success', 'Account updated.');
    }

    public function destroy(FinancialAccount $account): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        if ($account->movements()->exists()) {
            return back()->with('error', 'Cannot delete account with movements.');
        }

        $account->delete();
        return back()->with('success', 'Account deleted.');
    }

    public function show(FinancialAccount $account): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $movements = AccountMovement::where('account_id', $account->id)
            ->with('createdBy')
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->paginate(50);

        $balance = $account->balance();

        return view('finance.accounts.show', compact('account', 'movements', 'balance'));
    }

    // Group CRUD (parent_id = null)
    public function storeGroup(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'direction' => 'required|in:expense,income',
        ]);

        TransactionCategory::create([...$data, 'parent_id' => null]);
        return back()->with('success', 'Group created.');
    }

    public function destroyGroup(TransactionCategory $group): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        if ($group->children()->exists()) {
            return back()->with('error', 'Cannot delete group with categories under it. Delete the categories first.');
        }

        $group->delete();
        return back()->with('success', 'Group deleted.');
    }

    // Category CRUD (leaf — has parent_id)
    public function storeCategory(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'name'      => 'required|string|max:100',
            'parent_id' => 'required|uuid|exists:transaction_categories,id',
        ]);

        $parent = TransactionCategory::findOrFail($data['parent_id']);
        TransactionCategory::create([
            'name'      => $data['name'],
            'parent_id' => $parent->id,
            'direction' => $parent->direction,
        ]);

        return back()->with('success', 'Category created.');
    }

    public function destroyCategory(TransactionCategory $category): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        if ($category->expenseRecords()->exists() || $category->incomeRecords()->exists()) {
            return back()->with('error', 'Cannot delete category with existing records.');
        }

        $category->delete();
        return back()->with('success', 'Category deleted.');
    }
}
