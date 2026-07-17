<?php

namespace Crater\Http\Controllers\V1\Customer\Expense;

use Crater\Http\Controllers\Controller;
use Crater\Http\Resources\Customer\ExpenseResource;
use Crater\Models\Company;
use Crater\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ExpensesController extends Controller
{
    public function index(Request $request)
    {
        $customerId = (int) Auth::guard('customer')->id();
        $limit = $request->input('limit', 10);

        $expenses = Expense::with(['category', 'creator', 'fields'])
            ->where('customer_id', $customerId)
            ->applyFilters($request->only([
                'expense_category_id',
                'from_date',
                'to_date',
                'orderByField',
                'orderBy',
            ]))
            ->paginateData($limit);

        return ExpenseResource::collection($expenses)
            ->additional(['meta' => [
                'expenseTotalCount' => Expense::where('customer_id', $customerId)->count(),
            ]]);
    }

    public function show(Company $company, $id)
    {
        $expense = $company->expenses()
            ->where('customer_id', Auth::guard('customer')->id())
            ->whereKey($id)
            ->first();

        if (! $expense) {
            return response()->json(['error' => 'expense_not_found'], 404);
        }

        return new ExpenseResource($expense);
    }
}
