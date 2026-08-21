<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\Company;
use App\Models\FinancialAccount;
use App\Models\Income;
use App\Models\TransactionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class IncomeController extends Controller
{
    public function index(Request $request): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $from = $request->get('from', now()->startOfYear()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $query = Income::with(['category', 'company', 'lead', 'targetAccount', 'createdBy'])
            ->whereBetween('date', [$from, $to])
            ->orderByDesc('date')
            ->orderByDesc('created_at');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('company_id')) {
            $query->where('company_id', $request->company_id);
        }

        $incomes    = $query->get();
        $categories = TransactionCategory::forIncomes()->get();
        $accounts   = FinancialAccount::whereIn('type', ['bank', 'cash'])->where('is_active', true)->orderBy('name')->get();
        $spCompanies = Company::where('type', 'service_provider')->orderBy('name')->get();

        $totalsByCurrency = $incomes->groupBy('currency')->map(fn ($g) => $g->sum('amount'));

        return view('finance.incomes.index', compact(
            'incomes', 'categories', 'accounts', 'spCompanies',
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
            'company_id'        => 'nullable|uuid|exists:companies,id',
            'target_account_id' => 'nullable|uuid|exists:financial_accounts,id',
            'document'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        $income = Income::create([...$data, 'created_by' => auth()->id()]);

        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->storeAs(
                'finance-docs/incomes',
                $income->id . '.' . $file->getClientOriginalExtension(),
                'local'
            );
            $income->update(['document_path' => $path]);
        }

        // SP cari: company owes us → positive movement
        if ($income->company_id) {
            $company = Company::find($income->company_id);
            $account = FinancialAccount::forCompany($company, $income->currency);

            AccountMovement::create([
                'account_id'   => $account->id,
                'date'         => $income->date,
                'amount'       => $income->amount,
                'description'  => $income->description ?? ($income->category->name ?? 'Income'),
                'movable_type' => Income::class,
                'movable_id'   => $income->id,
                'created_by'   => auth()->id(),
            ]);
        }

        // Target bank/cash: if payment already received, credit the account
        if ($income->target_account_id) {
            AccountMovement::create([
                'account_id'   => $income->target_account_id,
                'date'         => $income->date,
                'amount'       => $income->amount,
                'description'  => $income->description ?? ($income->category->name ?? 'Income'),
                'movable_type' => Income::class,
                'movable_id'   => $income->id,
                'created_by'   => auth()->id(),
            ]);
        }

        return back()->with('success', 'Income recorded.');
    }

    public function destroy(Income $income): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        AccountMovement::where('movable_type', Income::class)
            ->where('movable_id', $income->id)
            ->delete();

        if ($income->document_path) {
            \Storage::disk('local')->delete($income->document_path);
        }

        $income->delete();
        return back()->with('success', 'Income deleted.');
    }
}
