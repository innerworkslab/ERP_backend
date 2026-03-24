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
        Schema::create('currencies', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->unique();
            $table->string('code', 10)->unique();
            $table->string('symbol', 10)->nullable();
            $table->decimal('exchange_rate', 15, 4)->default(1.0000);
            $table->boolean('is_base_currency')->default(true);
            $table->date('last_exchange_rate_update')->nullable();
            $table->timestamps();

            $table->unique(['name', 'code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('currencies');
    }
};
