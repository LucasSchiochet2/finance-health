<?php

use App\Models\DietMeal;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates a diet meal for a user', function () {
    $user = User::factory()->create();

    $response = $this->postJson("/api/diet/{$user->id}", [
        'date' => '2026-06-10',
        'status' => 'perfeito',
        'observation' => 'Dia bem alinhado.',
    ]);

    $response
        ->assertCreated()
        ->assertJsonFragment([
            'user_id' => $user->id,
            'date' => '2026-06-10',
            'status' => 'perfeito',
            'observation' => 'Dia bem alinhado.',
        ]);

    expect(DietMeal::where('user_id', $user->id)
        ->where('date', '2026-06-10')
        ->where('status', 'perfeito')
        ->exists())->toBeTrue();
});

it('returns chart summaries for diet meals', function () {
    $user = User::factory()->create();

    DietMeal::create([
        'user_id' => $user->id,
        'date' => '2026-06-10',
        'status' => 'perfeito',
    ]);

    DietMeal::create([
        'user_id' => $user->id,
        'date' => '2026-06-10',
        'status' => 'bom',
    ]);

    DietMeal::create([
        'user_id' => $user->id,
        'date' => '2026-06-11',
        'status' => 'medio',
    ]);

    DietMeal::create([
        'user_id' => $user->id,
        'date' => '2026-06-11',
        'status' => 'fora',
    ]);

    $response = $this->getJson("/api/diet/{$user->id}/charts?start_date=2026-06-01&end_date=2026-06-30");

    $response
        ->assertOk()
        ->assertJsonPath('total_meals', 4)
        ->assertJsonPath('total_days', 2)
        ->assertJsonPath('score_average', 56.25)
        ->assertJsonPath('by_status.0.status', 'perfeito')
        ->assertJsonPath('by_status.0.count', 1)
        ->assertJsonPath('by_day.0.date', '2026-06-10')
        ->assertJsonPath('by_month.0.month', '2026-06');
});
