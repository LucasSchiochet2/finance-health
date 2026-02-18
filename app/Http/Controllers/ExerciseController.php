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
    public function index(Request $request, User $user)
    {
        // Start query for system exercises (user_id is null) OR user's exercises
        $query = Exercise::where(function ($q) use ($user) {
            $q->whereNull('user_id')
              ->orWhere('user_id', $user->id);
        });

        // Apply search filter if present
        if ($request->has('search') && !empty($request->search)) {
            $search = $request->search;
            $query->whereRaw('LOWER(name) LIKE ?', ['%' . strtolower($search) . '%']);
        }

        $exercises = $query->get();

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
        // Debugging logs
        \Illuminate\Support\Facades\Log::info("addLog called for User: {$user->id}, Exercise ID: {$id}");

        // Find the exercise first (ignoring ownership) to see if it exists
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => "Exercise with ID {$id} not found."], 404);
        }

        // Check ownership/permission
        // Allow if system exercise (user_id is null) OR belongs to the user
        if ($exercise->user_id !== null && $exercise->user_id != $user->id) {
            return response()->json([
                'message' => "Access denied. Exercise belongs to User {$exercise->user_id}, but request is for User {$user->id}."
            ], 403);
        }

        $validated = $request->validate([
            'date' => 'required|date',
            'weight' => 'nullable|numeric',
            'reps' => 'nullable|integer',
            'sets' => 'nullable|integer',
            'observation' => 'nullable|string',
            'intensity' => 'nullable|numeric|min:0|max:10',
        ]);

        $log = new ExerciseLog($validated);
        $log->user_id = $user->id;
        $log->exercise_id = $exercise->id;
        $log->save();

        return response()->json($log, 201);
    }

    /**
     * Get logs for a specific exercise and user.
     */
    public function getLogs(User $user, string $id)
    {
        // Ensure exercise exists first
        $exercise = Exercise::find($id);

        if (!$exercise) {
            return response()->json(['message' => "Exercise with ID {$id} not found."], 404);
        }

        $logs = ExerciseLog::where('user_id', $user->id)
            ->where('exercise_id', $id)
            ->orderBy('date', 'desc')
            ->get();

        return response()->json($logs);
    }
}
