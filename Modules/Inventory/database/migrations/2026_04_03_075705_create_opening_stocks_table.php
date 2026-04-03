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
        Schema::create('opening_stocks', function (Blueprint $table) {
            $table->id();
            $table->string('voucher_no')->unique();
            $table->date('voucher_date');
            $table->unsignedBigInteger('inventory_id');
            $table->enum('status', ['pending', 'confirmed'])->default('pending');
            $table->text('remarks')->nullable();
            $table->decimal('total_amount', 15, 2)->default(0);

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->unsignedBigInteger('confirmed_by')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamps();

            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('confirmed_by')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_stocks');
    }
};
