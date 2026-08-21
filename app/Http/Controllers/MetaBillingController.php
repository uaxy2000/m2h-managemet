<?php

namespace App\Http\Controllers;

use App\Models\AccountMovement;
use App\Models\Expense;
use App\Models\FinancialAccount;
use App\Models\MetaInsight;
use App\Models\TransactionCategory;
use App\Services\MetaAdsService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class MetaBillingController extends Controller
{
    public function index(): View
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        // Use locally synced meta_insights data grouped by month (account level)
        $months = MetaInsight::where('entity_type', 'account')
            ->selectRaw("DATE_FORMAT(date, '%Y-%m') as month, SUM(spend) as total_spend, MIN(date) as date_start, MAX(date) as date_stop")
            ->groupByRaw("DATE_FORMAT(date, '%Y-%m')")
            ->orderByDesc('month')
            ->get()
            ->map(fn($r) => [
                'id'         => 'meta-month-' . $r->month,
                'month'      => $r->month,
                'date_start' => $r->date_start,
                'date_stop'  => $r->date_stop,
                'amount'     => round((float)$r->total_spend, 2),
                'status'     => 'SYNCED',
            ])
            ->all();

        // Determine ad account currency
        $currency = 'USD';
        try {
            $service = app(MetaAdsService::class);
            if ($service->isConfigured()) {
                $currency = $service->fetchAccountCurrency();
            }
        } catch (\Throwable) {}

        // Mark already-imported months
        $importedIds = Expense::where('description', 'LIKE', 'meta_billing:%')
            ->pluck('description')
            ->mapWithKeys(fn($d) => [substr($d, strlen('meta_billing:')) => true])
            ->all();

        $accounts   = FinancialAccount::whereIn('type', ['bank', 'cash', 'credit_card'])
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $categories = TransactionCategory::forExpenses()->with('children')->get();

        $lastSynced = MetaInsight::max('synced_at');

        return view('finance.meta-billing', compact(
            'months', 'currency', 'importedIds',
            'accounts', 'categories', 'lastSynced'
        ));
    }

    public function import(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()->isInternalAdmin(), 403);

        $data = $request->validate([
            'transaction_id'    => 'required|string|max:100',
            'date'              => 'required|date',
            'amount'            => 'required|numeric|min:0.01',
            'currency'          => 'required|in:TRY,EUR,USD,GBP',
            'category_id'       => 'required|uuid|exists:transaction_categories,id',
            'source_account_id' => 'nullable|uuid|exists:financial_accounts,id',
        ]);

        $key = 'meta_billing:' . $data['transaction_id'];

        if (Expense::where('description', $key)->exists()) {
            return back()->with('error', 'This transaction has already been imported.');
        }

        $expense = Expense::create([
            'date'              => $data['date'],
            'category_id'       => $data['category_id'],
            'amount'            => $data['amount'],
            'currency'          => $data['currency'],
            'description'       => $key,
            'source_account_id' => $data['source_account_id'] ?? null,
            'status'            => 'approved',
            'created_by'        => auth()->id(),
        ]);

        if ($data['source_account_id']) {
            AccountMovement::create([
                'account_id'   => $data['source_account_id'],
                'date'         => $data['date'],
                'amount'       => -$data['amount'],
                'description'  => 'Meta Ads billing #' . $data['transaction_id'],
                'movable_type' => Expense::class,
                'movable_id'   => $expense->id,
                'created_by'   => auth()->id(),
            ]);
        }

        return back()->with('success', 'Transaction imported to expenses.');
    }
}
