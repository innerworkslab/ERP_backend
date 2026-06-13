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
        Schema::create('goods_receive_notes_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receive_note_id');
            $table->unsignedBigInteger('purchase_order_line_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');
            $table->decimal('ordered_quantity', 15, 2);
            $table->decimal('previously_received_quantity', 15, 2);
            $table->decimal('remaining_quantity', 15, 2);
            $table->decimal('received_quantity', 15, 2);
            $table->decimal('good_quantity', 15, 2);
            $table->decimal('short_quantity', 15, 2)->default(0);
            $table->enum('discrepancy_reason', ['none', 'cashback', 'defect'])->default('none');
            $table->enum('defect_responsibility', ['none','supplier_side', 'company_side'])->nullable();
            $table->decimal('unit_price', 15, 2);
            $table->decimal('line_weight', 15, 4)->default(0);
            $table->decimal('allocated_charge_amount', 15, 2)->default(0);
            $table->decimal('allocated_tax_amount', 15, 2)->default(0);
            $table->decimal('final_unit_cost', 15, 4)->default(0);
            $table->decimal('line_total', 15, 2);
            $table->string('remarks')->nullable();
            $table->timestamps();

            $table->foreign('goods_receive_note_id')->references('id')->on('goods_receive_notes')->onDelete('cascade');
            $table->foreign('purchase_order_line_id')->references('id')->on('purchase_order_lines')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('unit_of_measurements')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receive_notes_lines');
    }
};
