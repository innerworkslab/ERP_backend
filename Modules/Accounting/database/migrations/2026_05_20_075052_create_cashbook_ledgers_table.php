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
        Schema::create('cashbook_ledgers', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cashbook_id')
                ->constrained('cashbooks')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignId('cashbook_transaction_id')
                ->constrained('cashbook_transactions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->dateTime('transaction_datetime');

            $table->enum('transaction_type', [
                'in',
                'out',
            ]);

            $table->decimal('amount', 24, 8);

            $table->decimal('before_balance', 24, 8);

            $table->decimal('after_balance', 24, 8);

            $table->string('remark')->nullable();

            $table->text('description')->nullable();

            $table->timestamps();

            $table->index([
                'cashbook_id',
                'transaction_datetime',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbook_ledgers');
    }
};
