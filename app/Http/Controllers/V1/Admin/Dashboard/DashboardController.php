<?php

namespace Crater\Http\Controllers\V1\Admin\Dashboard;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Models\Company;
use Crater\Models\CompanySetting;
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

        $invoiceTotals = [];
        $expenseTotals = [];
        $receiptTotals = [];
        $netIncomeTotals = [];
        $months = [];

        $fiscalYear = CompanySetting::getSetting('fiscal_year', $company->id) ?: '1-12';
        $fiscalStartMonth = (int) explode('-', $fiscalYear)[0];
        $startDate = Carbon::now();
        $start = Carbon::now();
        $end = Carbon::now();

        if ($fiscalStartMonth <= $start->month) {
            $startDate->month($fiscalStartMonth)->startOfMonth();
            $start->month($fiscalStartMonth)->startOfMonth();
            $end->month($fiscalStartMonth)->endOfMonth();
        } else {
            $startDate->subYear()->month($fiscalStartMonth)->startOfMonth();
            $start->subYear()->month($fiscalStartMonth)->startOfMonth();
            $end->subYear()->month($fiscalStartMonth)->endOfMonth();
        }

        if ($request->has('previous_year')) {
            $startDate->subYear()->startOfMonth();
            $start->subYear()->startOfMonth();
            $end->subYear()->endOfMonth();
        }

        for ($monthCounter = 0; $monthCounter < 12; $monthCounter++) {
            $range = [$start->format('Y-m-d'), $end->format('Y-m-d')];
            $invoiceTotal = Invoice::whereBetween('invoice_date', $range)->sum('base_total');
            $expenseTotal = Expense::whereBetween('expense_date', $range)->sum('base_amount');
            $receiptTotal = Payment::whereBetween('payment_date', $range)->sum('base_amount');

            $invoiceTotals[] = $invoiceTotal;
            $expenseTotals[] = $expenseTotal;
            $receiptTotals[] = $receiptTotal;
            $netIncomeTotals[] = $receiptTotal - $expenseTotal;
            $months[] = $start->format('M');

            $start->addMonth()->startOfMonth();
            $end->addMonth()->endOfMonth();
        }

        $periodEnd = $start->copy()->subMonth()->endOfMonth();
        $period = [$startDate->format('Y-m-d'), $periodEnd->format('Y-m-d')];

        $totalSales = Invoice::whereBetween('invoice_date', $period)->sum('base_total');
        $totalReceipts = Payment::whereBetween('payment_date', $period)->sum('base_amount');
        $totalExpenses = Expense::whereBetween('expense_date', $period)->sum('base_amount');

        $recentDueInvoices = Invoice::with('customer')
            ->where('base_due_amount', '>', 0)
            ->take(5)
            ->latest()
            ->get();
        $recentEstimates = Estimate::with('customer')->take(5)->latest()->get();

        return response()->json([
            'total_amount_due' => Invoice::sum('base_due_amount'),
            'total_customer_count' => Customer::count(),
            'total_invoice_count' => Invoice::count(),
            'total_estimate_count' => Estimate::count(),
            'recent_due_invoices' => BouncerFacade::can('view-invoice', Invoice::class) ? $recentDueInvoices : [],
            'recent_estimates' => BouncerFacade::can('view-estimate', Estimate::class) ? $recentEstimates : [],
            'chart_data' => [
                'months' => $months,
                'invoice_totals' => $invoiceTotals,
                'expense_totals' => $expenseTotals,
                'receipt_totals' => $receiptTotals,
                'net_income_totals' => $netIncomeTotals,
            ],
            'total_sales' => $totalSales,
            'total_receipts' => $totalReceipts,
            'total_expenses' => $totalExpenses,
            'total_net_income' => (int) $totalReceipts - (int) $totalExpenses,
        ]);
    }
}
