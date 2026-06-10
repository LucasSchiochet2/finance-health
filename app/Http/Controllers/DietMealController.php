<?php

namespace App\Http\Controllers;

use App\Models\DietMeal;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Validation\Rule;

class DietMealController extends Controller
{
    public function index(Request $request, User $user)
    {
        $filters = $request->validate([
            'date' => 'nullable|date',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'meal_type' => ['nullable', Rule::in(DietMeal::MEAL_TYPES)],
            'status' => ['nullable', Rule::in(DietMeal::STATUSES)],
        ]);

        $query = DietMeal::where('user_id', $user->id);
        $this->applyFilters($query, $filters);

        $meals = $query
            ->orderByDesc('date')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($meals);
    }

    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'meal_type' => ['required', Rule::in(DietMeal::MEAL_TYPES)],
            'status' => ['required', Rule::in(DietMeal::STATUSES)],
            'observation' => 'nullable|string',
        ]);

        $validated['user_id'] = $user->id;

        $meal = DietMeal::create($validated);

        return response()->json($meal, 201);
    }

    public function show(User $user, string $id)
    {
        $meal = DietMeal::where('user_id', $user->id)->findOrFail($id);

        return response()->json($meal);
    }

    public function update(Request $request, User $user, string $id)
    {
        $meal = DietMeal::where('user_id', $user->id)->findOrFail($id);

        $validated = $request->validate([
            'date' => 'sometimes|date',
            'meal_type' => ['sometimes', Rule::in(DietMeal::MEAL_TYPES)],
            'status' => ['sometimes', Rule::in(DietMeal::STATUSES)],
            'observation' => 'nullable|string',
        ]);

        $meal->update($validated);

        return response()->json($meal);
    }

    public function destroy(User $user, string $id)
    {
        $meal = DietMeal::where('user_id', $user->id)->findOrFail($id);
        $meal->delete();

        return response()->json(null, 204);
    }

    public function charts(Request $request, User $user)
    {
        $filters = $request->validate([
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after_or_equal:start_date',
            'meal_type' => ['nullable', Rule::in(DietMeal::MEAL_TYPES)],
            'status' => ['nullable', Rule::in(DietMeal::STATUSES)],
        ]);

        $query = DietMeal::where('user_id', $user->id);
        $this->applyFilters($query, $filters);

        $meals = $query->orderBy('date')->get();
        $totalMeals = $meals->count();

        return response()->json([
            'filters' => [
                'start_date' => $filters['start_date'] ?? null,
                'end_date' => $filters['end_date'] ?? null,
                'meal_type' => $filters['meal_type'] ?? null,
                'status' => $filters['status'] ?? null,
            ],
            'total_meals' => $totalMeals,
            'total_days' => $meals->pluck('date')->map(fn ($date) => $date->format('Y-m-d'))->unique()->count(),
            'score_average' => $this->averageScore($meals),
            'by_meal_type' => $this->mealTypeSummary($meals),
            'by_status' => $this->statusSummary($meals),
            'by_day' => $this->dailySummary($meals),
            'by_month' => $this->monthlySummary($meals),
        ]);
    }

    private function applyFilters(Builder $query, array $filters): void
    {
        if (!empty($filters['date'])) {
            $query->whereDate('date', $filters['date']);
        }

        if (!empty($filters['start_date'])) {
            $query->whereDate('date', '>=', $filters['start_date']);
        }

        if (!empty($filters['end_date'])) {
            $query->whereDate('date', '<=', $filters['end_date']);
        }

        if (!empty($filters['meal_type'])) {
            $query->where('meal_type', $filters['meal_type']);
        }

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
    }

    private function mealTypeSummary(Collection $meals): array
    {
        $totalMeals = $meals->count();
        $counts = $this->mealTypeCounts($meals);

        return collect(DietMeal::MEAL_TYPES)
            ->map(function (string $mealType) use ($counts, $totalMeals) {
                $count = $counts[$mealType];

                return [
                    'meal_type' => $mealType,
                    'label' => DietMeal::mealTypeLabel($mealType),
                    'count' => $count,
                    'percentage' => $totalMeals > 0 ? round(($count / $totalMeals) * 100, 2) : 0,
                ];
            })
            ->values()
            ->all();
    }

    private function statusSummary(Collection $meals): array
    {
        $totalMeals = $meals->count();
        $counts = $this->statusCounts($meals);

        return collect(DietMeal::STATUSES)
            ->map(function (string $status) use ($counts, $totalMeals) {
                $count = $counts[$status];

                return [
                    'status' => $status,
                    'label' => DietMeal::statusLabel($status),
                    'count' => $count,
                    'percentage' => $totalMeals > 0 ? round(($count / $totalMeals) * 100, 2) : 0,
                    'score' => DietMeal::statusScore($status),
                ];
            })
            ->values()
            ->all();
    }

    private function dailySummary(Collection $meals): array
    {
        return $meals
            ->groupBy(fn (DietMeal $meal) => $meal->date->format('Y-m-d'))
            ->map(function (Collection $dayMeals, string $date) {
                return [
                    'date' => $date,
                    'total_meals' => $dayMeals->count(),
                    'score_average' => $this->averageScore($dayMeals),
                    'predominant_status' => $this->predominantStatus($dayMeals),
                    'counts' => $this->statusCounts($dayMeals),
                ];
            })
            ->values()
            ->all();
    }

    private function monthlySummary(Collection $meals): array
    {
        return $meals
            ->groupBy(fn (DietMeal $meal) => $meal->date->format('Y-m'))
            ->map(function (Collection $monthMeals, string $month) {
                return [
                    'month' => $month,
                    'total_meals' => $monthMeals->count(),
                    'total_days' => $monthMeals->pluck('date')->map(fn ($date) => $date->format('Y-m-d'))->unique()->count(),
                    'score_average' => $this->averageScore($monthMeals),
                    'predominant_status' => $this->predominantStatus($monthMeals),
                    'counts' => $this->statusCounts($monthMeals),
                ];
            })
            ->values()
            ->all();
    }

    private function mealTypeCounts(Collection $meals): array
    {
        $grouped = $meals->groupBy('meal_type')->map->count();

        return collect(DietMeal::MEAL_TYPES)
            ->mapWithKeys(fn (string $mealType) => [$mealType => $grouped->get($mealType, 0)])
            ->all();
    }

    private function statusCounts(Collection $meals): array
    {
        $grouped = $meals->groupBy('status')->map->count();

        return collect(DietMeal::STATUSES)
            ->mapWithKeys(fn (string $status) => [$status => $grouped->get($status, 0)])
            ->all();
    }

    private function averageScore(Collection $meals): float
    {
        if ($meals->isEmpty()) {
            return 0;
        }

        return round($meals->avg(fn (DietMeal $meal) => DietMeal::statusScore($meal->status)), 2);
    }

    private function predominantStatus(Collection $meals): ?string
    {
        $counts = $this->statusCounts($meals);
        $highestCount = max($counts);

        if ($highestCount === 0) {
            return null;
        }

        foreach (DietMeal::STATUSES as $status) {
            if ($counts[$status] === $highestCount) {
                return $status;
            }
        }

        return null;
    }
}
