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
        $user = auth()->user();
        abort_unless($user->isInternalAdmin(), 403);

        $from = $request->get('from', now()->startOfYear()->toDateString());
        $to   = $request->get('to', now()->toDateString());

        $query = Expense::with(['category.parent', 'paidBy', 'sourceAccount', 'lead', 'createdBy'])
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->orderBy('created_at');

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }
        if ($request->filled('currency')) {
            $query->where('currency', $request->currency);
        }
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $expenses      = $query->get();
        $categories    = TransactionCategory::forExpenses()->with('children')->get();
        $accounts      = FinancialAccount::whereIn('type', ['bank', 'cash'])->where('is_active', true)->orderBy('name')->get();
        $internalUsers = User::whereHas('company', fn ($q) => $q->where('type', 'internal'))->orderBy('name')->get();

        $totalsByCurrency = $expenses->where('status', 'approved')->groupBy('currency')->map(fn ($g) => $g->sum('amount'));
        $pendingCount     = $expenses->where('status', 'pending')->count();

        return view('finance.expenses.index', compact(
            'expenses', 'categories', 'accounts', 'internalUsers',
            'from', 'to', 'totalsByCurrency', 'pendingCount'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user->isInternalAdmin() || $user->isInternalMember(), 403);

        $isMember = $user->isInternalMember();

        $data = $request->validate([
            'date'              => 'required|date',
            'category_id'       => 'required|uuid|exists:transaction_categories,id',
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'required|in:TRY,EUR,USD,GBP',
            'description'       => 'nullable|string|max:1000',
            'lead_id'           => 'nullable|uuid|exists:leads,id',
            'paid_by_user_id'   => 'nullable|uuid|exists:users,id',
            'source_account_id' => 'nullable|uuid|exists:financial_accounts,id',
            'document'          => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
        ]);

        // Members always pay personally; admin must not alter this
        if ($isMember) {
            $data['paid_by_user_id']   = $user->id;
            $data['source_account_id'] = null;
        }

        $status = $isMember ? 'pending' : 'approved';

        $expense = Expense::create([
            ...$data,
            'status'     => $status,
            'created_by' => auth()->id(),
        ]);

        // Attach document
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = $file->storeAs(
                'finance-docs/expenses',
                $expense->id . '.' . $file->getClientOriginalExtension(),
                'local'
            );
            $expense->update(['document_path' => $path]);
        }

        // Create cari movements only for approved expenses
        if ($status === 'approved') {
            $this->createMovements($expense);
        }

        $message = $isMember
            ? 'Expense submitted and pending admin approval.'
            : 'Expense recorded.';

        return back()->with('success', $message);
    }

    public function approve(Expense $expense): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);
        abort_if($expense->status === 'approved', 400);

        $expense->update(['status' => 'approved']);
        $this->createMovements($expense);

        return back()->with('success', 'Expense approved.');
    }

    public function reject(Expense $expense): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $expense->update(['status' => 'rejected']);
        return back()->with('success', 'Expense rejected.');
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        AccountMovement::where('movable_type', Expense::class)
            ->where('movable_id', $expense->id)
            ->delete();

        if ($expense->document_path) {
            \Storage::disk('local')->delete($expense->document_path);
        }

        $expense->delete();
        return back()->with('success', 'Expense deleted.');
    }

    private function createMovements(Expense $expense): void
    {
        $desc = $expense->description ?? ($expense->category?->name ?? 'Expense');

        if ($expense->paid_by_user_id) {
            $user    = User::find($expense->paid_by_user_id);
            $account = FinancialAccount::forUser($user, $expense->currency);

            AccountMovement::create([
                'account_id'   => $account->id,
                'date'         => $expense->date,
                'amount'       => -$expense->amount,
                'description'  => $desc,
                'movable_type' => Expense::class,
                'movable_id'   => $expense->id,
                'created_by'   => $expense->created_by,
            ]);
        }

        if ($expense->source_account_id) {
            AccountMovement::create([
                'account_id'   => $expense->source_account_id,
                'date'         => $expense->date,
                'amount'       => -$expense->amount,
                'description'  => $desc,
                'movable_type' => Expense::class,
                'movable_id'   => $expense->id,
                'created_by'   => $expense->created_by,
            ]);
        }
    }
}
