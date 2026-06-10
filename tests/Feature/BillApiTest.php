<?php

use App\Models\Bill;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('filters bills by category name', function () {
    $user = User::factory()->create();

    Bill::create([
        'user_id' => $user->id,
        'name' => 'Aluguel',
        'amount' => 1200,
        'due_date' => '2026-06-10',
        'category_name' => 'Fixa',
    ]);

    Bill::create([
        'user_id' => $user->id,
        'name' => 'Mercado',
        'amount' => 450,
        'due_date' => '2026-06-12',
        'category_name' => 'Variável',
    ]);

    $response = $this->getJson("/api/bills/{$user->id}?category_name=Fixa");

    $response
        ->assertOk()
        ->assertJsonPath('data.0.total_count', 1)
        ->assertJsonPath('data.0.bills.0.name', 'Aluguel')
        ->assertJsonPath('data.0.bills.0.category_name', 'Fixa');
});

it('filters bills by fixed or variable bill type using category name', function () {
    $user = User::factory()->create();

    Bill::create([
        'user_id' => $user->id,
        'name' => 'Condomínio',
        'amount' => 600,
        'due_date' => '2026-06-05',
        'category_name' => 'Fixas',
    ]);

    Bill::create([
        'user_id' => $user->id,
        'name' => 'Farmácia',
        'amount' => 90,
        'due_date' => '2026-06-08',
        'category_name' => 'Variável',
    ]);

    $fixedResponse = $this->getJson("/api/bills/{$user->id}?bill_type=fixa");
    $variableResponse = $this->getJson("/api/bills/{$user->id}?bill_type=variavel");

    $fixedResponse
        ->assertOk()
        ->assertJsonPath('data.0.total_count', 1)
        ->assertJsonPath('data.0.bills.0.name', 'Condomínio');

    $variableResponse
        ->assertOk()
        ->assertJsonPath('data.0.total_count', 1)
        ->assertJsonPath('data.0.bills.0.name', 'Farmácia');
});
