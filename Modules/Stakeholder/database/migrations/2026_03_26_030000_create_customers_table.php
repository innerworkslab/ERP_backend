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
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('code', 50)->unique();
            $table->string('name', 150);
            $table->string('company_name', 150)->nullable();
            $table->string('phone_number', 30)->nullable();
            $table->string('country', 100)->nullable();
            $table->string('town', 100)->nullable();
            $table->string('township', 100)->nullable();
            $table->text('address')->nullable();
            $table->string('bank_acc', 100)->nullable();
            $table->unsignedBigInteger('branch_id')->nullable();
            $table->decimal('credit_limit', 18, 2)->default(0);
            $table->decimal('opening', 18, 2)->default(0);
            $table->unsignedBigInteger('customer_type_id');
            $table->date('birthday')->nullable();
            $table->string('payment_terms', 100)->nullable();
            $table->string('payment_due', 100)->nullable();
            $table->enum('status', ['Active', 'Inactive'])->default('Active');
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
