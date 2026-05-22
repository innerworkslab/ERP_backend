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
        Schema::create('accounts', function (Blueprint $table) {
            $table->id();

            $table->foreignId('parent_account_id')
                ->nullable()
                ->constrained('accounts')
                ->nullOnDelete();

            $table->string('code')->unique();

            $table->string('name');

            $table->enum('type', [
                'Heading',
                'Asset',
                'Fixed Asset',
                'Contra Asset',
                'Cash',
                'Inventory',
                'Receivable',
                'Accounts Receivable',
                'Prepaid Expense',
                'Deposit',
                'Liability',
                'Accounts Payable',
                'Customer Deposit',
                'Equity',
                'Income',
                'COGS',
                'Expense'
            ]);

            $table->enum('division', [
                'SOFP',
                'P&L',
                'Trading'
            ])->nullable();

            $table->text('description')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('accounts');
    }
};
