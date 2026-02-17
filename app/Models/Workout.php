<?php

namespace App\Models;

use Backpack\CRUD\app\Models\Traits\CrudTrait;
use Illuminate\Database\Eloquent\Model;

class Workout extends Model
{
    use CrudTrait;

    protected $fillable = [
        'name',
        'description',
        'user_id',
        'default_reps',
        'default_sets',
        'observation'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function exercises()
    {
        return $this->belongsToMany(Exercise::class, 'workout_exercises')
            ->withPivot(['reps', 'sets', 'order'])
            ->withTimestamps();
    }
}
