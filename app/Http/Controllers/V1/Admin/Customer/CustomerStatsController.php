<?php

namespace Crater\Http\Controllers\V1\Admin\Customer;

use Carbon\Carbon;
use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\CustomerResource;
use Crater\Models\CompanySetting;
use Crater\Models\Customer;
use Crater\Models\Expense;
use Crater\Models\Invoice;
use Crater\Models\Payment;
use Illuminate\Http\Request;

class CustomerStatsController extends Controller
{
    public function __invoke(Request $request, Customer $customer)
    {
        $this->authorize('view', $customer);

        $invoiceTotals = [];
        $expenseTotals = [];
        $receiptTotals = [];
        $netProfits = [];
        $months = [];

        $fiscalYear = CompanySetting::getSetting('fiscal_year', $request->header('company')) ?: '1-12';
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
            $invoiceTotal = Invoice::whereBetween('invoice_date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ])->whereCustomer($customer->id)->sum('total');

            $expenseTotal = Expense::whereBetween('expense_date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ])->where('customer_id', $customer->id)->sum('amount');

            $receiptTotal = Payment::whereBetween('payment_date', [
                $start->format('Y-m-d'),
                $end->format('Y-m-d'),
            ])->whereCustomer($customer->id)->sum('amount');

            $invoiceTotals[] = $invoiceTotal;
            $expenseTotals[] = $expenseTotal;
            $receiptTotals[] = $receiptTotal;
            $netProfits[] = $receiptTotal - $expenseTotal;
            $months[] = $start->format('M');

            $start->addMonth()->startOfMonth();
            $end->addMonth()->endOfMonth();
        }

        $periodEnd = $start->copy()->subMonth()->endOfMonth();
        $period = [$startDate->format('Y-m-d'), $periodEnd->format('Y-m-d')];

        $salesTotal = Invoice::whereBetween('invoice_date', $period)
            ->whereCustomer($customer->id)
            ->sum('total');
        $totalReceipts = Payment::whereBetween('payment_date', $period)
            ->whereCustomer($customer->id)
            ->sum('amount');
        $totalExpenses = Expense::whereBetween('expense_date', $period)
            ->where('customer_id', $customer->id)
            ->sum('amount');

        return (new CustomerResource($customer->fresh()))
            ->additional(['meta' => [
                'chartData' => [
                    'months' => $months,
                    'invoiceTotals' => $invoiceTotals,
                    'expenseTotals' => $expenseTotals,
                    'receiptTotals' => $receiptTotals,
                    'netProfit' => (int) $totalReceipts - (int) $totalExpenses,
                    'netProfits' => $netProfits,
                    'salesTotal' => $salesTotal,
                    'totalReceipts' => $totalReceipts,
                    'totalExpenses' => $totalExpenses,
                ],
            ]]);
    }
}
