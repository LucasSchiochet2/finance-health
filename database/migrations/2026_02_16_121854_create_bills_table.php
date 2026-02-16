<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('bills', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('description')->nullable();
            $table->decimal('amount', 10, 2);
            $table->date('due_date');
            $table->integer('is_recurring')->default(0);
            $table->integer('recurring_interval')->nullable();
            $table->boolean('paid')->default(false);
            $table->string('payment_method')->nullable();
            $table->integer('is_installment')->default(0);
            $table->integer('installment_count')->nullable();
            $table->integer('installment_current')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bills');
    }
};
