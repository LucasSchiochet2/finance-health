<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request, User $user)
    {
        $filters = $request->validate([
            'category_name' => 'nullable|string|max:255',
            'bill_type' => 'nullable|string|max:50',
        ]);

        $query = Bill::where('user_id', $user->id)
            ->with(['category', 'creditCard'])
            ->orderBy('due_date', 'asc');

        if (!empty($filters['category_name'])) {
            $query->where('category_name', $filters['category_name']);
        }

        if (!empty($filters['bill_type'])) {
            $categoryNames = $this->categoryNamesForBillType($filters['bill_type']);

            if ($categoryNames) {
                $query->whereRaw('LOWER(category_name) in (' . implode(',', array_fill(0, count($categoryNames), '?')) . ')', $categoryNames);
            }
        }

        $bills = $query->get();

        $grouped = $bills->groupBy(function ($bill) {
            return \Carbon\Carbon::parse($bill->due_date)->format('Y-m');
        });

        $monthlyData = $grouped->map(function ($monthBills, $month) {
            $monthTotal = $monthBills->sum('amount');

            $byCategory = $monthBills->groupBy('category_bill_id')->map(function ($group) use ($monthTotal) {
                $categoryTotal = $group->sum('amount');
                $percentage = $monthTotal > 0 ? round(($categoryTotal / $monthTotal) * 100, 2) : 0;

                $categoryName = $group->first()->category ? $group->first()->category->name : 'Sem Categoria';

                return [
                    'category_id' => $group->first()->category_bill_id,
                    'category_name' => $categoryName,
                    'total_amount' => $categoryTotal,
                    'percentage' => $percentage,
                    'count' => $group->count(),
                ];
            })->values();

            return [
                'month' => $month,
                'total_amount' => $monthTotal,
                'total_count' => $monthBills->count(),
                'summary_by_category' => $byCategory,
                'bills' => $monthBills
            ];
        });

        return response()->json([
             'data' => $monthlyData->values()
        ]);
    }

    private function categoryNamesForBillType(string $billType): array
    {
        return match (strtolower($billType)) {
            'fixa', 'fixas', 'fixed' => ['fixa', 'fixas'],
            'variavel', 'variaveis', 'variável', 'variáveis', 'variable' => ['variavel', 'variaveis', 'variável', 'variáveis'],
            default => [],
        };
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'category_name' => 'nullable|string|max:255',
            'category_bill_id' => 'required|exists:category_bills,id',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
            'notification_enabled' => 'nullable|boolean',
        ]);

        $bill = new Bill($request->all());

        // If user is authenticated, associate the bill with them
        if (Auth::check()) {
            $bill->user_id = Auth::id();
        }

        if ($request->has('is_installment') && $request->is_installment) {
            $bill->installment_current = 1;
            $bill->group_id = \Illuminate\Support\Str::uuid();
        } elseif ($request->has('is_recurring') && $request->is_recurring) {
            $bill->group_id = \Illuminate\Support\Str::uuid();
        }

        $bill->save();

        if ($bill->is_installment && $bill->installment_count > 1) {
            $totalInstallments = (int)$bill->installment_count;
            $startDate = \Carbon\Carbon::parse($bill->due_date);

            for ($i = 2; $i <= $totalInstallments; $i++) {
                $installment = $bill->replicate();
                $installment->installment_current = $i;
                $installment->due_date = $startDate->copy()->addMonths($i - 1);
                $installment->paid = false;
                $installment->save();
            }
        }

        if ($bill->is_recurring && !$bill->is_installment) {
            $startDate = \Carbon\Carbon::parse($bill->due_date);

            for ($i = 1; $i <= 5; $i++) {
                $recurringBill = $bill->replicate();
                $recurringBill->due_date = $startDate->copy()->addMonths($i);
                $recurringBill->paid = false;
                $recurringBill->save();
            }
        }

        return response()->json($bill, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(string $userId, string $id)
    {
        $bill = Bill::where('user_id', $userId)
            ->with(['category', 'creditCard'])
            ->findOrFail($id);

        return response()->json($bill);
    }
    public function showByCategory(Request $request, User $user, string $categoryId)
    {
        $query = Bill::where('user_id', $user->id)
            ->where('category_bill_id', $categoryId)
            ->with(['category', 'creditCard'])
            ->orderBy('due_date', 'asc');

        if ($request->has('month')) {
            $month = $request->query('month');
            // Check if month format matches Y-m default or handle both
            $query->where('due_date', 'like', "{$month}%");
        }

        $bills = $query->get();

        $grouped = $bills->groupBy(function ($bill) {
            return \Carbon\Carbon::parse($bill->due_date)->format('Y-m');
        });

        $monthlyData = $grouped->map(function ($monthBills, $month) {
            $monthTotal = $monthBills->sum('amount');

            return [
                'month' => $month,
                'total_amount' => $monthTotal,
                'total_count' => $monthBills->count(),
                'bills' => $monthBills
            ];
        });

        return response()->json([
            'data' => $monthlyData->values()
        ]);
    }

    public function spendingByCategory(Request $request, User $user)
    {
        $filters = $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'category_name' => 'nullable|string|max:255',
            'paid' => 'nullable|boolean',
            'payment_method' => 'nullable|string|max:255',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
        ]);

        $query = Bill::where('user_id', $user->id);

        if (!empty($filters['month'])) {
            $query->where('due_date', 'like', "{$filters['month']}%");
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('due_date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('due_date', '<=', $filters['end_date']);
        }

        if (!empty($filters['category_name'])) {
            $query->where('category_name', $filters['category_name']);
        }

        if ($request->has('paid')) {
            $query->where('paid', filter_var($filters['paid'], FILTER_VALIDATE_BOOLEAN));
        }

        if (!empty($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (!empty($filters['credit_card_id'])) {
            $query->where('credit_card_id', $filters['credit_card_id']);
        }

        $bills = $query->get();
        $totalAmount = $bills->sum('amount');

        $summary = $bills
            ->groupBy(function ($bill) {
                return $bill->category_name ?: 'Sem Categoria';
            })
            ->map(function ($group, $categoryName) use ($totalAmount) {
                $categoryTotal = $group->sum('amount');

                return [
                    'category_name' => $categoryName,
                    'total_amount' => $categoryTotal,
                    'percentage' => $totalAmount > 0 ? round(($categoryTotal / $totalAmount) * 100, 2) : 0,
                    'count' => $group->count(),
                ];
            })
            ->sortByDesc('total_amount')
            ->values();

        return response()->json([
            'total_amount' => $totalAmount,
            'total_count' => $bills->count(),
            'data' => $summary,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $userId, string $id)
    {
        $validated = $request->validate([
            'category_name' => 'nullable|string|max:255',
            'credit_card_id' => 'nullable|exists:credit_cards,id',
        ]);

        $bill = Bill::where('user_id', $userId)->findOrFail($id);
        $originalBill = $bill->replicate();

        // Capture state before update
        $wasInstallment = $bill->is_installment;

        $bill->update($request->all());

        if ($request->has('update_all') && $request->update_all && $bill->group_id) {
            $billsToUpdate = Bill::where('user_id', $userId)
                ->where('group_id', $bill->group_id)
                ->where('id', '!=', $bill->id)
                ->get();

            foreach ($billsToUpdate as $b) {
                // Update common fields
                $b->name = $bill->name;
                $b->amount = $bill->amount;
                $b->category_name = $bill->category_name;
                $b->category_bill_id = $bill->category_bill_id;
                $b->credit_card_id = $bill->credit_card_id;
                // Add other shared fields if necessary
                $b->save();
            }
        }

        $bill->refresh();

        if ($bill->is_installment && $bill->installment_count > 1) {

             if (!$wasInstallment || !$bill->installment_current) {
                 $bill->installment_current = 1;
                 $bill->save();
             }
             if ($bill->installment_current == 1) {
                $totalInstallments = (int)$bill->installment_count;
                $startDate = \Carbon\Carbon::parse($bill->due_date);

                for ($i = 2; $i <= $totalInstallments; $i++) {
                    $existsBill = Bill::where('user_id', $userId)
                        ->where('name', $originalBill->name) // Check with original Name
                        ->where('installment_current', $i)
                        ->where('group_id', $bill->group_id) // Ensure same group
                        ->first();

                    if (!$existsBill) {
                        $installment = $bill->replicate();
                        $installment->installment_current = $i;
                        $installment->due_date = $startDate->copy()->addMonths($i - 1);
                        $installment->paid = false;
                        $installment->save();
                    } else {
                        // Update existing installment if update_all is true (handled above)
                        // or if we need to regenerate/link
                         if (!$existsBill->group_id) {
                            $existsBill->update(['group_id' => $bill->group_id]);
                         }
                    }
                }
             }
        }

        return response()->json($bill);
    }

    public function notify(User $user)
    {
        $today = now()->format('Y-m-d');

        $bills = Bill::where('user_id', $user->id)
            ->whereDate('due_date', $today)
            ->where('notification_enabled', true)
            ->with(['category', 'creditCard'])
            ->get();

        /*
         * Se a conta for de cartão de crédito, o vencimento da fatura
         * será a data definida no cadastro do cartão (expiration_date).
         * Caso contrário, null ou a própria data de vencimento da conta.
         */
        $bills->transform(function ($bill) {
            $bill->invoice_due_date = $bill->creditCard ? $bill->creditCard->expiration_date : null;
            return $bill;
        });

        return response()->json($bills);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $userId, string $id)
    {
        $bill = Bill::where('user_id', $userId)->findOrFail($id);

        if ($request->has('delete_all') && filter_var($request->query('delete_all'), FILTER_VALIDATE_BOOLEAN) && $bill->group_id) {
            // Delete all bills in the same group
            Bill::where('user_id', $userId)->where('group_id', $bill->group_id)->delete();
        } else {
            $bill->delete();
        }

        return response()->json(null, 204);
    }
}
