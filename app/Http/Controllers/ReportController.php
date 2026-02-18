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
        // 1. Get all Debit Expenses (Grouped by Year-Month)
        // logic: due_date in Month X, credit_card_id is null
        $debitExpenses = Bill::where('user_id', $user->id)
            ->whereNull('credit_card_id')
            ->selectRaw('YEAR(due_date) as year, MONTH(due_date) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->orderBy('year', 'desc')
            ->orderBy('month', 'desc')
            ->get()
            ->keyBy(function ($item) {
                return $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT);
            });

        // 2. Get all Credit Card Expenses (Grouped by Year-Month)
        // logic: due_date in Month X-1, credit_card_id is NOT null
        // These are expenses MADE in Month X-1, but paid in Month X.
        $creditCardExpenses = Bill::where('user_id', $user->id)
            ->whereNotNull('credit_card_id')
            ->selectRaw('YEAR(due_date) as year, MONTH(due_date) as month, SUM(amount) as total')
            ->groupBy('year', 'month')
            ->get()
            ->keyBy(function ($item) {
                // To align with the report month (payment month), we shift this forward by 1 month.
                // Expense in Jan 2024 (2024-01) -> Report for Feb 2024 (2024-02)
                $date = Carbon::createFromDate($item->year, $item->month, 1)->addMonth();
                return $date->year . '-' . str_pad($date->month, 2, '0', STR_PAD_LEFT);
            });

        // 3. Merge all relevant months
        $allMonths = $debitExpenses->keys()->merge($creditCardExpenses->keys())->unique()->sort()->values();

        $reportData = [];

        foreach ($allMonths as $monthKey) {
            list($year, $month) = explode('-', $monthKey);
            $year = (int)$year;
            $month = (int)$month;

            $debitAmount = isset($debitExpenses[$monthKey]) ? $debitExpenses[$monthKey]->total : 0;
            $creditAmount = isset($creditCardExpenses[$monthKey]) ? $creditCardExpenses[$monthKey]->total : 0;

            $total = $debitAmount + $creditAmount;

            // Calculate Salary Percentage (using current salary)
            $salary = $user->salary;
            $percentage = $salary > 0 ? round(($total / $salary) * 100, 2) : 0;

            $reportData[] = [
                'month' => $month,
                'year' => $year,
                'user_salary' => $salary,
                'debit_expenses_current_month' => $debitAmount,
                'credit_card_invoice_previous_month' => $creditAmount, // This is expenses from Previous Month paid in This Month
                'total_spend_for_month' => $total, // This is the total cash outflow for This Month
                'spend_percentage_of_salary' => $percentage . '%',
            ];
        }

        // Return most recent first
        return response()->json([
            'data' => array_reverse($reportData),
            'message' => 'Monthly spend history calculated based on: (Non-Credit Card items due in month X) + (Credit Card items from month X-1)'
        ]);
    }
}
