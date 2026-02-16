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
    public function index(User $user)
    {
        $bills = Bill::where('user_id', $user->id)
            ->with('category')
            ->orderBy('due_date', 'asc')
            ->get();

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

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'amount' => 'required|numeric',
            'due_date' => 'required|date',
            'category_bill_id' => 'required|exists:category_bills,id',
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
    public function show(string $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);
        return response()->json($bill);
    }
    public function showByCategory(User $user, string $categoryId)
    {
        $bills = Bill::where('user_id', $user->id)
            ->where('category_bill_id', $categoryId)
            ->with('category')
            ->orderBy('due_date', 'asc')
            ->get();

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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        // Capture state before update
        $wasInstallment = $bill->is_installment;

        $bill->update($request->all());

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
                    $existsBill = Bill::where('user_id', Auth::id())
                        ->where('name', $bill->name)
                        ->where('installment_current', $i)
                        ->whereDate('due_date', $startDate->copy()->addMonths($i - 1))
                        ->first();

                    if (!$existsBill) {
                        $installment = $bill->replicate();
                        $installment->installment_current = $i;
                        $installment->due_date = $startDate->copy()->addMonths($i - 1);
                        $installment->paid = false;
                        $installment->save();
                    } else {
                        // Update group_id if it's missing
                         if (!$existsBill->group_id) {
                            $existsBill->update(['group_id' => $bill->group_id]);
                         }
                    }
                }
             }
        }

        return response()->json($bill);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        if ($bill->group_id) {
            // Delete all bills in the same group
            Bill::where('user_id', Auth::id())->where('group_id', $bill->group_id)->delete();
        } else {
            // If no group_id but it's an installment, try to delete by best guess only if requested?
            // User asked "if I delete, it deletes (all)?" implying they want it to.
            // But without group_id it's unsafe. Just delete the single one for legacy data.
            $bill->delete();
        }

        return response()->json(null, 204);
    }
}
