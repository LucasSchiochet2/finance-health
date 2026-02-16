<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class BillController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Return bills only for the authenticated user with categories
        $bills = Bill::where('user_id', Auth::id())->with('category')->get();

        // Calculate totals
        $totalAmount = $bills->sum('amount');
        $totalCount = $bills->count();

        // Calculate stats per category
        $byCategory = $bills->groupBy('category_bill_id')->map(function ($group) use ($totalAmount) {
            $categoryTotal = $group->sum('amount');
            $percentage = $totalAmount > 0 ? round(($categoryTotal / $totalAmount) * 100, 2) : 0;

            // Handle potentially missing category relation
            $categoryName = $group->first()->category ? $group->first()->category->name : 'Sem Categoria';

            return [
                'category_id' => $group->first()->category_bill_id,
                'category_name' => $categoryName,
                'total_amount' => $categoryTotal,
                'percentage' => $percentage,
                'count' => $group->count(),
            ];
        })->values(); // Reset keys to array

        return response()->json([
            'summary' => [
                'total_amount' => $totalAmount,
                'total_count' => $totalCount,
                'by_category' => $byCategory
            ],
            'bills' => $bills
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

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);

        $bill->update($request->all());

        return response()->json($bill);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $bill = Bill::where('user_id', Auth::id())->findOrFail($id);
        $bill->delete();

        return response()->json(null, 204);
    }
}
