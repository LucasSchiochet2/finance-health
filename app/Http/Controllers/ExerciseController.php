<?php

namespace App\Http\Controllers;

use App\Models\Exercise;
use App\Models\ExerciseLog;
use App\Models\User;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(User $user)
    {
        // Return system exercises (user_id is null) AND user's exercises
        $exercises = Exercise::whereNull('user_id')
            ->orWhere('user_id', $user->id)
            ->get();

        return response()->json($exercises);
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user, string $id)
    {
        // Find exercise if it belongs to user OR is system default
        $exercise = Exercise::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereNull('user_id');
            })
            ->firstOrFail();

        return response()->json($exercise);
    }

    /**
     * Store a newly created exercise in storage.
     */
    public function store(Request $request, User $user)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'muscle_group' => 'nullable|string',
            'photo_url' => 'nullable|url',
        ]);

        $exercise = new Exercise($validated);
        $exercise->user_id = $user->id;
        $exercise->save();

        return response()->json($exercise, 201);
    }

    /**
     * Remove the specified exercise from storage.
     */
    public function destroy(User $user, string $id)
    {
        $exercise = Exercise::where('user_id', $user->id)->findOrFail($id);
        $exercise->delete();

        return response()->json(null, 204);
    }

    /**
     * Store a newly created log for an exercise.
     */
    public function addLog(Request $request, User $user, string $id)
    {
        // First, check if the exercise exists and user has permission
        // A user can add logs to their own exercises OR system exercises (where user_id is null)
        $exercise = Exercise::where('id', $id)
            ->where(function ($query) use ($user) {
                $query->where('user_id', $user->id)
                      ->orWhereNull('user_id');
            })
            ->first();

        if (!$exercise) {
            return response()->json(['message' => 'Exercise not found or access denied'], 404);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'weight' => 'nullable|numeric',
            'reps' => 'nullable|integer',
            'sets' => 'nullable|integer',
            'observation' => 'nullable|string',
        ]);

        $log = new ExerciseLog($validated);
        $log->user_id = $user->id;
        $log->exercise_id = $exercise->id;
        $log->save();

        return response()->json($log, 201);
    }
}
