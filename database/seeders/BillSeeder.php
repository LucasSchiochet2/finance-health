<?php

namespace Database\Seeders;

use App\Models\Bill;
use App\Models\CategoryBill;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class BillSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Get the test user
        $user = User::where('email', 'test@example.com')->first();

        if (!$user) {
             $user = User::factory()->create([
                'name' => 'Test User',
                'email' => 'test@example.com',
                'password' => bcrypt('password'),
            ]);
        }

        // Create 3 Categories
        $categories = [];
        $categoriesData = [
            ['name' => 'Alimentação', 'icon' => 'fa-utensils'],
            ['name' => 'Transporte', 'icon' => 'fa-car'],
            ['name' => 'Lazer', 'icon' => 'fa-gamepad'],
        ];

        foreach ($categoriesData as $catData) {
            $categories[] = CategoryBill::create($catData);
        }

        // Create 10 Bills
        $billsData = [
            // Alimentação
            [
                'name' => 'Supermercado Mensal',
                'amount' => 850.50,
                'category_index' => 0,
                'due_date' => Carbon::parse('2026-02-20'),
                'paid' => false
            ],
            [
                'name' => 'Ifood Lanche',
                'amount' => 45.90,
                'category_index' => 0,
                'due_date' => Carbon::parse('2026-02-14'),
                'paid' => true
            ],
            [
                'name' => 'Restaurante Fim de Semana',
                'amount' => 120.00,
                'category_index' => 0,
                'due_date' => Carbon::parse('2026-02-15'),
                'paid' => true
            ],

            // Transporte
            [
                'name' => 'Uber Trabalho',
                'amount' => 25.00,
                'category_index' => 1,
                'due_date' => Carbon::parse('2026-02-18'),
                'paid' => false
            ],
            [
                'name' => 'Combustível',
                'amount' => 200.00,
                'category_index' => 1,
                'due_date' => Carbon::parse('2026-02-25'),
                'paid' => false
            ],
            [
                'name' => 'Manutenção Carro',
                'amount' => 450.00,
                'category_index' => 1,
                'due_date' => Carbon::parse('2026-02-10'),
                'paid' => true
            ],

            // Lazer
            [
                'name' => 'Cinema',
                'amount' => 60.00,
                'category_index' => 2,
                'due_date' => Carbon::parse('2026-02-12'),
                'paid' => true
            ],
            [
                'name' => 'Assinatura Streaming',
                'amount' => 39.90,
                'category_index' => 2,
                'due_date' => Carbon::parse('2026-02-28'),
                'paid' => false
            ],
            [
                'name' => 'Show',
                'amount' => 250.00,
                'category_index' => 2,
                'due_date' => Carbon::parse('2026-03-05'),
                'paid' => false
            ],
            [
                'name' => 'Jogo Steam',
                'amount' => 150.00,
                'category_index' => 2,
                'due_date' => Carbon::parse('2026-02-16'),
                'paid' => true
            ],
        ];

        foreach ($billsData as $billData) {
            Bill::create([
                'name' => $billData['name'],
                'amount' => $billData['amount'],
                'category_bill_id' => $categories[$billData['category_index']]->id,
                'user_id' => 1,
                'due_date' => $billData['due_date'],
                'paid' => $billData['paid'],
                'description' => 'Conta gerada automaticamente pelo seeder',
                'payment_method' => 'credit_card',
                'is_recurring' => false,
            ]);
        }
    }
}
