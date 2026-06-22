<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cashbook_transactions', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cashbook_id')
                ->constrained('cashbooks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('source_account_id')
                ->constrained('accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('destination_account_id')
                ->constrained('accounts')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('transaction_type', [
                'in',
                'out',
            ]);

            $table->enum('category', [
                'expense',
                'income',
                'transfer',
                'adjustment',
                'other',
            ]);

            $table->dateTime('transaction_datetime');

            $table->foreignId('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->decimal('amount', 24, 8);

            $table->decimal('base_currency_amount', 24, 8);

            $table->string('reference_no')->unique();

            $table->string('remark')->nullable();

            $table->text('description')->nullable();

            $table->enum('status', [
                'pending',
                'confirmed',
                'cancelled'
            ])->default('pending');

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->index([
                'cashbook_id',
                'transaction_datetime',
            ]);

            $table->index([
                'transaction_type',
                'category',
                'status',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbook_transactions');
    }
};
