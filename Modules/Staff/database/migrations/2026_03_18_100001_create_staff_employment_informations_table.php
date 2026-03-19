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
        Schema::create('staff_employment_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->unique();
            $table->date('join_date')->nullable();
            $table->boolean('is_contract')->default(false);
            $table->string('off_day', 50)->nullable();
            $table->string('overtime_fee_type')->nullable();
            $table->decimal('salary', 15, 2)->nullable();
            $table->decimal('sale_incentive_amount', 15, 2)->nullable();
            $table->decimal('sale_commission', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_employment_informations');
    }
};
