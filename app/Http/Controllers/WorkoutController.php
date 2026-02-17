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
        $workout = Workout::find($id);

        if (!$workout) {
             return response()->json(['message' => "Workout ID {$id} not found"], 404);
        }

        if ($workout->user_id !== $user->id) {
             return response()->json(['message' => "Unauthorized. You can only update your own workouts."], 403);
        }

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

        // If exercises key is present (even if empty), sync relevant exercises
        if ($request->has('exercises')) {
             $syncData = [];
             // Log input for debugging
             \Illuminate\Support\Facades\Log::info('Workout Update Exercises Payload:', ['exercises' => $request->exercises]);

             if (is_array($request->exercises)) {
                 foreach ($request->exercises as $index => $exerciseData) {
                     // Skip if no ID provided (improper format)
                     if (!isset($exerciseData['id'])) continue;

                     $order = isset($exerciseData['order']) ? $exerciseData['order'] : ($index + 1);
                     $sets = $exerciseData['sets'] ?? $workout->default_sets;
                     $reps = $exerciseData['reps'] ?? $workout->default_reps;

                     $syncData[$exerciseData['id']] = [
                         'sets' => $sets,
                         'reps' => $reps,
                         'order' => $order
                     ];
                 }
             }
             // Sync will remove any exercises not in $syncData
             $workout->exercises()->sync($syncData);
        }

        return response()->json($workout->load('exercises'));
    }

    /**
     * Remove an exercise from a workout.
     */
    public function removeExercise(User $user, $workoutId, $exerciseId)
    {
        $workout = Workout::find($workoutId);

        if (!$workout) {
             return response()->json(['message' => "Workout ID {$workoutId} not found"], 404);
        }

        if ($workout->user_id !== $user->id) {
             return response()->json(['message' => "Unauthorized. You can only modify your own workouts."], 403);
        }

        // Detach the exercise from the workout
        $workout->exercises()->detach($exerciseId);

        return response()->json(['message' => 'Exercise removed from workout successfully.']);
    }
}
