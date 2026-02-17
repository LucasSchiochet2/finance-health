<?php

namespace App\Http\Controllers;

use App\Models\Workout;
use Illuminate\Http\Request;
use App\Models\User;
class WorkoutController extends Controller
{
    /**
     * Store a newly created workout.
     */
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'observation' => 'nullable|string',
            'default_sets' => 'nullable|integer|min:1',
            'default_reps' => 'nullable|integer|min:1',
            'exercises' => 'required|array|min:1',
            'exercises.*' => 'required|array', // Enforce object structure inside array
            'exercises.*.id' => 'required|exists:exercises,id', // Expect 'id' or 'exercise_id'
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.order' => 'nullable|integer',
        ]);

        $workout = new Workout();
        $workout->name = $request->name;
        $workout->description = $request->description;
        $workout->observation = $request->observation;
        $workout->default_sets = $request->default_sets;
        $workout->default_reps = $request->default_reps;
        $workout->user_id = $user->id;
        $workout->save();

        if ($request->has('exercises')) {
            foreach ($request->exercises as $index => $exerciseData) {
                // Determine order: use provided order, or index + 1
                $order = isset($exerciseData['order']) ? $exerciseData['order'] : ($index + 1);

                // Use exercise specific sets/reps if provided, otherwise fallback to workout default
                $sets = $exerciseData['sets'] ?? $workout->default_sets;
                $reps = $exerciseData['reps'] ?? $workout->default_reps;

                $workout->exercises()->attach($exerciseData['id'], [
                    'sets' => $sets,
                    'reps' => $reps,
                    'order' => $order,
                ]);
            }
        }

        // Reload keys
        return response()->json($workout->load('exercises'), 201);
    }

    public function index(Request $request, User $user)
    {
        $workouts = Workout::where(function($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereNull('user_id');
            })
            ->with(['exercises' => function($query) {
                $query->withPivot(['sets', 'reps', 'order'])->orderBy('workout_exercises.order');
            }])
            ->get();

        return response()->json($workouts);
    }

    public function show(User $user, $id)
    {
        $workout = Workout::with(['exercises' => function ($query) {
            $query->withPivot(['sets', 'reps', 'order'])->orderBy('workout_exercises.order');
        }])->findOrFail($id);

        if ($workout->user_id && $workout->user_id !== $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        return response()->json($workout);
    }

    /**
     * Update the specified workout in storage.
     */
    public function update(Request $request, User $user, $id)
    {
        $workout = Workout::where('id', $id)
            ->where('user_id', $user->id) // Only update own workouts
            ->firstOrFail();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'description' => 'nullable|string',
            'observation' => 'nullable|string',
            'default_sets' => 'nullable|integer|min:1',
            'default_reps' => 'nullable|integer|min:1',
            'exercises' => 'sometimes|array',
            'exercises.*.id' => 'required_with:exercises|exists:exercises,id',
            'exercises.*.sets' => 'nullable|integer|min:1',
            'exercises.*.reps' => 'nullable|integer|min:1',
            'exercises.*.order' => 'nullable|integer',
        ]);

        $workout->update($request->only('name', 'description', 'observation', 'default_sets', 'default_reps'));

        if ($request->has('exercises')) {
             $syncData = [];
             foreach ($request->exercises as $index => $exerciseData) {
                 $order = isset($exerciseData['order']) ? $exerciseData['order'] : ($index + 1);
                 $sets = $exerciseData['sets'] ?? $workout->default_sets;
                 $reps = $exerciseData['reps'] ?? $workout->default_reps;

                 $syncData[$exerciseData['id']] = [
                     'sets' => $sets,
                     'reps' => $reps,
                     'order' => $order
                 ];
             }
             $workout->exercises()->sync($syncData);
        }

        return response()->json($workout->load('exercises'));
    }
}
