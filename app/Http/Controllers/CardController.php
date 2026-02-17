<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CardController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        $cards = Card::where('user_id', $user->id)
            ->with(['bills' => function ($query) {
                $query->orderBy('due_date', 'asc');
            }])
            ->get();

        $data = $cards->map(function ($card) {

            $groupedBills = $card->bills->groupBy(function ($bill) {
                return \Carbon\Carbon::parse($bill->due_date)->format('Y-m');
            });

            $invoices = $groupedBills->map(function ($monthBills, $month) use ($card) {
                 $totalAmount = $monthBills->sum('amount');
                 // Calculate estimated due date based on month and expiration_day
                 // This is a simplification.

                 return [
                    'month' => $month,
                    'total_amount' => $totalAmount,
                    'count' => $monthBills->count(),
                    'bills' => $monthBills
                 ];
            })->values();

            return [
                'id' => $card->id,
                'name' => $card->name,
                'closing_day' => $card->closing_day,
                'expiration_date' => $card->expiration_date,
                'limit' => $card->limit,
                'invoices' => $invoices
            ];
        });

        return response()->json(['data' => $data]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'closing_day' => 'required|date', // Or integer day? Migration says 'date'. That's odd for a recurring day.
            'expiration_date' => 'required|date', // Same here.
            'limit' => 'required|numeric',
        ]);

        // Note: The migration defined closing_day and expiration_date as DATE columns.
        // Usually these are just the day number (e.g., 5th of the month).
        // If they are specific dates, it might be for a specific period?
        // But the model structure suggests they are properties of the card itself.
        // Let's assume the user inputs a full date for now as per schema.

        $card = new Card($request->all());
        $card->user_id = $user->id;
        $card->save();

        return response()->json($card, 201);
    }
}
