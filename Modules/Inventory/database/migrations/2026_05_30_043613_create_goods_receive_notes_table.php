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
        Schema::create('goods_receive_notes', function (Blueprint $table) {
            $table->id();
            $table->string('grn_no')->unique();
            $table->unsignedBigInteger('purchase_order_id');
            $table->unsignedBigInteger('supplier_id');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('currency_id');
            $table->date('grn_date');
            $table->enum('fee_allocation_method', ['by_weight', 'by_line_value', 'equal_qty', 'equal_line'])->default('by_line_value');
            $table->enum('tax_allocation_method', ['by_weight', 'by_products'])->default('by_weight');
            $table->text('remarks')->nullable();

            // amount
            $table->decimal('subtotal_amount', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('cargo_tax_amount', 15, 2)->default(0);
            $table->decimal('charge_total_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);

            //approval workflow
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->unsignedBigInteger('created_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            //foreign keys
            $table->foreign('purchase_order_id')->references('id')->on('purchase_orders')->onDelete('cascade');
            $table->foreign('supplier_id')->references('id')->on('suppliers')->onDelete('cascade');
            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receive_notes');
    }
};
