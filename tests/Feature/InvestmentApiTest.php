<?php

use App\Models\Bill;
use App\Models\Investment;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('creates an investment entrada and the automatic expense bill', function () {
    $user = User::factory()->create();

    $response = $this->postJson("/api/investments/{$user->id}", [
        'type' => 'entrada',
        'amount' => 500,
        'date' => '2026-06-10',
        'description' => 'Aporte Tesouro Direto',
    ]);

    $response
        ->assertCreated()
        ->assertJsonFragment([
            'user_id' => $user->id,
            'type' => 'entrada',
            'date' => '2026-06-10T00:00:00.000000Z',
            'description' => 'Aporte Tesouro Direto',
        ]);

    $investment = Investment::first();
    $bill = Bill::first();

    expect($investment)->not->toBeNull()
        ->and($bill)->not->toBeNull()
        ->and($investment->bill_id)->toBe($bill->id)
        ->and($bill->user_id)->toBe($user->id)
        ->and($bill->name)->toBe('Investimento')
        ->and($bill->category_name)->toBe('Investimento')
        ->and((float) $bill->amount)->toBe(500.0)
        ->and($bill->due_date)->toBe('2026-06-10')
        ->and((bool) $bill->paid)->toBeTrue();
});

it('summarizes investments with a single user goal', function () {
    $user = User::factory()->create();

    $this->putJson("/api/investments/{$user->id}/goal", [
        'amount' => 1000,
    ])->assertOk();

    $this->postJson("/api/investments/{$user->id}", [
        'type' => 'entrada',
        'amount' => 600,
        'date' => '2026-06-10',
    ])->assertCreated();

    $this->postJson("/api/investments/{$user->id}", [
        'type' => 'saida',
        'amount' => 100,
        'date' => '2026-06-12',
    ])->assertCreated();

    $this->postJson("/api/investments/{$user->id}", [
        'type' => 'entrada',
        'amount' => 200,
        'date' => '2026-07-01',
    ])->assertCreated();

    $response = $this->getJson("/api/investments/{$user->id}/summary");

    $response->assertOk();
    $data = $response->json();

    expect((float) $data['total_entrada'])->toBe(800.0)
        ->and((float) $data['total_saida'])->toBe(100.0)
        ->and((float) $data['total'])->toBe(700.0)
        ->and((float) $data['goal_amount'])->toBe(1000.0)
        ->and((float) $data['goal_progress_percentage'])->toBe(70.0)
        ->and($data['by_month'][0]['month'])->toBe('2026-06')
        ->and((float) $data['by_month'][0]['total'])->toBe(500.0)
        ->and($data['by_month'][1]['month'])->toBe('2026-07')
        ->and((float) $data['by_month'][1]['total'])->toBe(200.0);
});

it('keeps the automatic expense bill in sync with investment changes', function () {
    $user = User::factory()->create();

    $investmentId = $this->postJson("/api/investments/{$user->id}", [
        'type' => 'entrada',
        'amount' => 300,
        'date' => '2026-06-10',
    ])
        ->assertCreated()
        ->json('id');

    $this->putJson("/api/investments/{$user->id}/{$investmentId}", [
        'amount' => 350,
        'date' => '2026-06-11',
        'description' => 'Aporte ajustado',
    ])->assertOk();

    $bill = Bill::first();

    expect((float) $bill->amount)->toBe(350.0)
        ->and($bill->due_date)->toBe('2026-06-11')
        ->and($bill->description)->toBe('Aporte ajustado');

    $this->putJson("/api/investments/{$user->id}/{$investmentId}", [
        'type' => 'saida',
    ])->assertOk();

    expect(Bill::count())->toBe(0);
});
