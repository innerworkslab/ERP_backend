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
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->date('transaction_date');
            $table->enum('reference_type', ['opening_stock', 'purchase_receive', 'purchase_return', 'sale_issue' ,'sale_return', 'adjustment_in', 'adjustment_out','damage','transfer_in','transfer_out']);
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('voucher_no')->nullable();
            $table->unsignedBigInteger('product_id');
            $table->string('sku');
            $table->string('lot_no')->nullable();
            $table->unsignedBigInteger('inventory_id');
            $table->enum('movement_type', ['in', 'out']);
            $table->decimal('quantity', 15, 2);
            $table->unsignedBigInteger('uom_id');
            $table->decimal('unit_cost', 15, 2)->nullable();
            $table->decimal('total_cost', 15, 2)->nullable();
            $table->decimal('balance_quantity_before', 15, 2)->nullable();
            $table->decimal('balance_quantity_after', 15, 2)->nullable();
            $table->decimal('balance_cost_before', 15, 2)->nullable();
            $table->decimal('balance_cost_after', 15, 2)->nullable();
            $table->timestamps();

            $table->foreign('reference_id')->references('id')->on('stock_transfers')->onDelete('set null');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('unit_of_measurements')->onDelete('cascade');

            $table->index(['transaction_date', 'id']);
            $table->index('reference_type');
            $table->index('reference_id');
            $table->index('voucher_no');
            $table->index('product_id');
            $table->index('sku');
            $table->index('lot_no');
            $table->index('inventory_id');
            $table->index('uom_id');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stock_movements');
    }
};
