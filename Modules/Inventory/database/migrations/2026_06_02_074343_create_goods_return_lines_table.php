<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('goods_return_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_return_id');
            $table->unsignedBigInteger('goods_receive_note_line_id');
            $table->unsignedBigInteger('purchase_order_line_id');
            $table->unsignedBigInteger('product_id');
            $table->unsignedBigInteger('uom_id');
            $table->unsignedBigInteger('tax_id')->nullable();
            $table->decimal('return_quantity', 15, 2);
            $table->decimal('unit_price', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('line_total', 15, 2)->default(0);
            $table->string('reason')->nullable();
            $table->timestamps();

            $table->foreign('purchase_return_id')->references('id')->on('purchase_returns')->onDelete('cascade');
            $table->foreign('goods_receive_note_line_id')->references('id')->on('goods_receive_notes_lines')->onDelete('cascade');
            $table->foreign('purchase_order_line_id')->references('id')->on('purchase_order_lines')->onDelete('cascade');
            $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            $table->foreign('uom_id')->references('id')->on('unit_of_measurements')->onDelete('cascade');
            $table->foreign('tax_id')->references('id')->on('taxs')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('goods_return_lines');
    }
};
