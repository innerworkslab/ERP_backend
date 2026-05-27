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
        Schema::create('cashbook_transfers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_cashbook_id');
            $table->unsignedBigInteger('destination_cashbook_id');
            $table->datetime('transfer_datetime');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('base_currency_amount', 24, 8);
            $table->string('reference_no')->unique();
            $table->string('remark')->nullable();
            $table->enum('status', ['pending', 'confirmed','rejected'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('source_cashbook_id')->references('id')->on('cashbooks')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('destination_cashbook_id')->references('id')->on('cashbooks')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('currency_id')->references('id')->on('currencies')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->cascadeOnUpdate()->restrictOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->cascadeOnUpdate()->restrictOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbook_transfers');
    }
};
