<?php

namespace App\Http\Controllers;

use App\Models\Bill;
use App\Models\CategoryBill;
use App\Models\Investment;
use App\Models\InvestmentGoal;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class InvestmentController extends Controller
{
    public function index(Request $request, User $user)
    {
        $filters = $this->validateFilters($request);

        $query = Investment::where('user_id', $user->id)->with('bill');
        $this->applyFilters($query, $filters);

        $investments = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($investments);
    }

    public function store(Request $request, User $user)
    {
        $validated = $this->validateInvestment($request);

        $investment = DB::transaction(function () use ($validated, $user) {
            $investment = Investment::create([
                ...$validated,
                'user_id' => $user->id,
            ]);

            $this->syncExpenseBill($investment);

            return $investment->fresh('bill');
        });

        return response()->json($investment, 201);
    }

    public function show(User $user, string $id)
    {
        $investment = Investment::where('user_id', $user->id)
            ->with('bill')
            ->findOrFail($id);

        return response()->json($investment);
    }

    public function update(Request $request, User $user, string $id)
    {
        $investment = Investment::where('user_id', $user->id)->findOrFail($id);
        $validated = $this->validateInvestment($request, true);

        $investment = DB::transaction(function () use ($investment, $validated) {
            $investment->update($validated);
            $this->syncExpenseBill($investment);

            return $investment->fresh('bill');
        });

        return response()->json($investment);
    }

    public function destroy(User $user, string $id)
    {
        $investment = Investment::where('user_id', $user->id)
            ->with('bill')
            ->findOrFail($id);

        DB::transaction(function () use ($investment) {
            $investment->bill?->delete();
            $investment->delete();
        });

        return response()->json(null, 204);
    }

    public function summary(Request $request, User $user)
    {
        $filters = $this->validateFilters($request);

        $query = Investment::where('user_id', $user->id);
        $this->applyFilters($query, $filters);

        $investments = $query->orderBy('date')->get();
        $totalEntrada = $this->sumByType($investments, Investment::TYPE_ENTRADA);
        $totalSaida = $this->sumByType($investments, Investment::TYPE_SAIDA);
        $total = $totalEntrada - $totalSaida;
        $goalAmount = (float) (InvestmentGoal::where('user_id', $user->id)->value('amount') ?? 0);

        return response()->json([
            'filters' => [
                'month' => $filters['month'] ?? null,
                'start_date' => $filters['start_date'] ?? null,
                'end_date' => $filters['end_date'] ?? null,
                'type' => $filters['type'] ?? null,
            ],
            'total_entrada' => $totalEntrada,
            'total_saida' => $totalSaida,
            'total' => $total,
            'goal_amount' => $goalAmount,
            'goal_progress_percentage' => $goalAmount > 0 ? round(($total / $goalAmount) * 100, 2) : 0,
            'by_month' => $this->monthlySummary($investments),
        ]);
    }

    public function goal(User $user)
    {
        $goal = InvestmentGoal::where('user_id', $user->id)->first();

        return response()->json([
            'id' => $goal?->id,
            'user_id' => $user->id,
            'amount' => (float) ($goal?->amount ?? 0),
        ]);
    }

    public function updateGoal(Request $request, User $user)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
        ]);

        $goal = InvestmentGoal::updateOrCreate(
            ['user_id' => $user->id],
            ['amount' => $validated['amount']]
        );

        return response()->json($goal);
    }

    private function validateInvestment(Request $request, bool $isUpdate = false): array
    {
        $required = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'type' => [$required, Rule::in(Investment::TYPES)],
            'amount' => [$required, 'numeric', 'min:0.01'],
            'date' => [$required, 'date'],
            'description' => 'nullable|string|max:255',
        ]);
    }

    private function validateFilters(Request $request): array
    {
        return $request->validate([
            'month' => 'nullable|date_format:Y-m',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'type' => ['nullable', Rule::in(Investment::TYPES)],
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['month'])) {
            $month = Carbon::createFromFormat('Y-m', $filters['month'])->startOfMonth();
            $query->whereBetween('date', [$month->toDateString(), $month->copy()->endOfMonth()->toDateString()]);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
    }

    private function syncExpenseBill(Investment $investment): void
    {
        if ($investment->type !== Investment::TYPE_ENTRADA) {
            $investment->bill?->delete();
            $investment->forceFill(['bill_id' => null])->saveQuietly();

            return;
        }

        $category = CategoryBill::firstOrCreate(
            ['name' => 'Investimento'],
            ['icon' => 'fa-chart-line']
        );

        $billData = [
            'name' => 'Investimento',
            'description' => $investment->description ?: 'Investimento registrado automaticamente',
            'amount' => $investment->amount,
            'due_date' => $investment->date->toDateString(),
            'paid' => true,
            'payment_method' => 'investment',
            'category_name' => $category->name,
            'category_bill_id' => $category->id,
            'user_id' => $investment->user_id,
            'notification_enabled' => false,
        ];

        $bill = $investment->bill_id
            ? Bill::where('user_id', $investment->user_id)->find($investment->bill_id)
            : null;

        if ($bill) {
            $bill->update($billData);
        } else {
            $bill = Bill::create($billData);
            $investment->forceFill(['bill_id' => $bill->id])->saveQuietly();
        }
    }

    private function sumByType(Collection $investments, string $type): float
    {
        return (float) $investments
            ->where('type', $type)
            ->sum(fn (Investment $investment) => (float) $investment->amount);
    }

    private function monthlySummary(Collection $investments): array
    {
        return $investments
            ->groupBy(fn (Investment $investment) => $investment->date->format('Y-m'))
            ->map(function (Collection $monthInvestments, string $month) {
                $entrada = $this->sumByType($monthInvestments, Investment::TYPE_ENTRADA);
                $saida = $this->sumByType($monthInvestments, Investment::TYPE_SAIDA);

                return [
                    'month' => $month,
                    'total_entrada' => $entrada,
                    'total_saida' => $saida,
                    'total' => $entrada - $saida,
                    'total_count' => $monthInvestments->count(),
                ];
            })
            ->values()
            ->all();
    }
}
