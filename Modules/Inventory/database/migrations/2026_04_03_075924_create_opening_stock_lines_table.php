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
        Schema::create('opening_stock_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('opening_stock_id');
            $table->unsignedBigInteger('product_id');
            $table->decimal('quantity', 15, 2)->default(0);
            $table->unsignedBigInteger('uom_id');
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->decimal('subtotal', 15, 2)->default(0);
            $table->string('lot_no')->nullable();
            $table->date('expired_date')->nullable();
            $table->string('serial_no')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();

            $table->foreign('opening_stock_id')->references('id')->on('opening_stocks')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('unit_of_measurements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.S
     */
    public function down(): void
    {
        Schema::dropIfExists('opening_stock_lines');
    }
};
