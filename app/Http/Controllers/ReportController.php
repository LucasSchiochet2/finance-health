<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function getMonthlySpend(Request $request, User $user)
    {
        // Default to current month if not provided
        $date = $request->has('date') ? Carbon::parse($request->date) : Carbon::now();

        $month = $date->month;
        $year = $date->year;

        // 1. Non-Credit Card Expenses for the current month
        // logic: due_date in current month AND (credit_card_id is null) for the given user
        $debitExpenses = Bill::where('user_id', $user->id)
            ->whereMonth('due_date', $month)
            ->whereYear('due_date', $year)
            ->whereNull('credit_card_id')
            ->sum('amount');

        // 2. Credit Card Invoices from the *previous* month
        // Logic: Sum of bills with credit_card_id where due_date is in the PREVIOUS month for the given user
        // This represents the Credit Card Statement that is likely due/paid in the current month.

        $prevDate = $date->copy()->subMonth();
        $prevMonth = $prevDate->month;
        $prevYear = $prevDate->year;

        $creditCardExpenses = Bill::where('user_id', $user->id)
            ->whereMonth('due_date', $prevMonth)
            ->whereYear('due_date', $prevYear)
            ->whereNotNull('credit_card_id')
            ->sum('amount');

        $total = $debitExpenses + $creditCardExpenses;

        // Calculate Salary Percentage
        $salary = $user->salary;
        $percentage = $salary > 0 ? round(($total / $salary) * 100, 2) : 0;

        return response()->json([
            'month' => $month,
            'year' => $year,
            'user_salary' => $salary,
            'debit_expenses_current_month' => $debitExpenses,
            'credit_card_invoice_previous_month' => $creditCardExpenses,
            'total_spend_for_month' => $total,
            'spend_percentage_of_salary' => $percentage . '%',
            'message' => 'Total calculated based on: (Non-Credit Card items due this month) + (Credit Card items from previous month)'
        ]);
    }
}
