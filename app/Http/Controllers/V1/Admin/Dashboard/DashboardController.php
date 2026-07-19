<?php

namespace Crater\Http\Controllers\V1\Admin\Dashboard;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
use Crater\Models\CreditNote;
use Crater\Models\Customer;
use Crater\Models\Estimate;
use Crater\Models\Expense;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Illuminate\Http\Request;
use Silber\Bouncer\BouncerFacade;

class DashboardController extends Controller
{
    public function __invoke(Request $request)
    {
        $company = Company::findOrFail($request->header('company'));
        $this->authorize('view dashboard', $company);

        [$periodStart, $periodEnd] = $this->currentFiscalPeriod($company->id);
        $previousStart = $periodStart->copy()->subYear();
        $previousEnd = $periodEnd->copy()->subYear();

        $months = [];
        $invoiceTotals = [];
        $previousInvoiceTotals = [];
        $expenseTotals = [];
        $receiptTotals = [];
        $netIncomeTotals = [];

        $currentMonth = $periodStart->copy();
        $previousMonth = $previousStart->copy();

        for ($monthCounter = 0; $monthCounter < 12; $monthCounter++) {
            $currentRange = [
                $currentMonth->copy()->startOfMonth()->format('Y-m-d'),
                $currentMonth->copy()->endOfMonth()->format('Y-m-d'),
            ];
            $previousRange = [
                $previousMonth->copy()->startOfMonth()->format('Y-m-d'),
                $previousMonth->copy()->endOfMonth()->format('Y-m-d'),
            ];

            $invoiceTotal = Invoice::query()
                ->whereBetween('invoice_date', $currentRange)
                ->sum('base_sub_total');
            $previousInvoiceTotal = Invoice::query()
                ->whereBetween('invoice_date', $previousRange)
                ->sum('base_sub_total');
            $expenseTotal = Expense::query()
                ->whereBetween('expense_date', $currentRange)
                ->sum('base_amount');
            $receiptTotal = Payment::query()
                ->whereBetween('payment_date', $currentRange)
                ->sum('base_amount');

            $months[] = $currentMonth->format('M');
            $invoiceTotals[] = (int) $invoiceTotal;
            $previousInvoiceTotals[] = (int) $previousInvoiceTotal;
            $expenseTotals[] = (int) $expenseTotal;
            $receiptTotals[] = (int) $receiptTotal;
            $netIncomeTotals[] = (int) $receiptTotal - (int) $expenseTotal;

            $currentMonth->addMonth();
            $previousMonth->addMonth();
        }

        $currentPeriod = [$periodStart->format('Y-m-d'), $periodEnd->format('Y-m-d')];
        $previousPeriod = [$previousStart->format('Y-m-d'), $previousEnd->format('Y-m-d')];

        $totalSalesHt = (int) Invoice::query()
            ->whereBetween('invoice_date', $currentPeriod)
            ->sum('base_sub_total');
        $previousSalesHt = (int) Invoice::query()
            ->whereBetween('invoice_date', $previousPeriod)
            ->sum('base_sub_total');
        $totalReceipts = (int) Payment::query()
            ->whereBetween('payment_date', $currentPeriod)
            ->sum('base_amount');
        $previousReceipts = (int) Payment::query()
            ->whereBetween('payment_date', $previousPeriod)
            ->sum('base_amount');
        $totalExpenses = (int) Expense::query()
            ->whereBetween('expense_date', $currentPeriod)
            ->sum('base_amount');

        $recentInvoices = Invoice::query()
            ->with('customer')
            ->latest('invoice_date')
            ->latest('id')
            ->take(4)
            ->get();
        $recentEstimates = Estimate::query()
            ->with('customer')
            ->latest('estimate_date')
            ->latest('id')
            ->take(4)
            ->get();

        $overdueQuery = Invoice::query()
            ->where('base_due_amount', '>', 0)
            ->whereDate('due_date', '<', now()->toDateString());
        $pendingQuery = Invoice::query()
            ->where('base_due_amount', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('due_date')
                    ->orWhereDate('due_date', '>=', now()->toDateString());
            });
        $pendingEstimateQuery = Estimate::query()->whereIn('status', [
            Estimate::STATUS_DRAFT,
            Estimate::STATUS_SENT,
            Estimate::STATUS_VIEWED,
        ]);

        return response()->json([
            'fiscal_period' => [
                'label' => 'Exercice '.$periodEnd->year,
                'start' => $periodStart->format('Y-m-d'),
                'end' => $periodEnd->format('Y-m-d'),
            ],
            'total_amount_due' => (int) Invoice::query()->sum('base_due_amount'),
            'total_customer_count' => Customer::query()->count(),
            'total_invoice_count' => Invoice::query()->count(),
            'total_estimate_count' => Estimate::query()->count(),
            'pending_invoice_count' => (clone $pendingQuery)->count(),
            'pending_estimate_count' => (clone $pendingEstimateQuery)->count(),
            'pending_estimate_amount' => (int) (clone $pendingEstimateQuery)->sum('base_total'),
            'recent_due_invoices' => BouncerFacade::can('view-invoice', Invoice::class) ? $recentInvoices : [],
            'recent_estimates' => BouncerFacade::can('view-estimate', Estimate::class) ? $recentEstimates : [],
            'chart_data' => [
                'months' => $months,
                'invoice_totals' => $invoiceTotals,
                'previous_invoice_totals' => $previousInvoiceTotals,
                'expense_totals' => $expenseTotals,
                'receipt_totals' => $receiptTotals,
                'net_income_totals' => $netIncomeTotals,
            ],
            'invoice_distribution' => [
                'paid' => Invoice::query()->where('paid_status', Invoice::STATUS_PAID)->count(),
                'pending' => (clone $pendingQuery)->count(),
                'overdue' => (clone $overdueQuery)->count(),
                'credit_notes' => CreditNote::query()->count(),
            ],
            'total_sales' => $totalSalesHt,
            'total_sales_ht' => $totalSalesHt,
            'previous_sales_ht' => $previousSalesHt,
            'sales_growth_percent' => $this->growthPercent($totalSalesHt, $previousSalesHt),
            'total_receipts' => $totalReceipts,
            'previous_receipts' => $previousReceipts,
            'receipts_growth_percent' => $this->growthPercent($totalReceipts, $previousReceipts),
            'total_expenses' => $totalExpenses,
            'total_net_income' => $totalReceipts - $totalExpenses,
        ]);
    }

    /** @return array{0: Carbon, 1: Carbon} */
    private function currentFiscalPeriod(int $companyId): array
    {
        $fiscalYear = CompanySetting::getSetting('fiscal_year', $companyId) ?: '1-12';
        $fiscalStartMonth = max(1, min(12, (int) explode('-', $fiscalYear)[0]));
        $now = Carbon::now();
        $startYear = $now->month >= $fiscalStartMonth ? $now->year : $now->year - 1;
        $start = Carbon::create($startYear, $fiscalStartMonth, 1)->startOfDay();
        $end = $start->copy()->addMonths(12)->subDay()->endOfDay();

        return [$start, $end];
    }

    private function growthPercent(int $current, int $previous): float
    {
        if ($previous === 0) {
            return $current > 0 ? 100.0 : 0.0;
        }

        return round((($current - $previous) / $previous) * 100, 1);
    }
}
