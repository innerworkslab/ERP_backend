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
        Schema::create('sale_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_number')->unique();
            $table->dateTime('invoice_date');
            $table->unsignedBigInteger('branch_id');
            $table->unsignedBigInteger('inventory_id');
            $table->unsignedBigInteger('customer_id');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('exchange_rate', 10, 4)->default(0);
            $table->unsignedBigInteger('selling_price_group_id');
            $table->enum('payment_terms', ['net30', 'net60' ,'due_on_receipt','advanced_payment','cash_on_delivery'])->default('due_on_receipt');
            $table->date('payment_due_date')->nullable();
            $table->enum('payment_status', ['paid', 'unpaid', 'partial'])->default('unpaid');
            $table->enum('status', ['draft', 'pending', 'ordered', 'reserved', 'delivered'])->default('draft');
            $table->enum('inventory_transaction_method', ['FIFO', 'LIFO', 'custom_batch'])->default('FIFO');
            $table->string('remarks')->nullable();
            $table->unsignedBigInteger('sell_tax_id');
            $table->decimal('sub_total', 10, 2)->default(0);
            $table->decimal('items_discount', 10, 2)->default(0);
            $table->enum('invoice_discount_type', ['fixed', 'percentage'])->default('fixed');
            $table->decimal('invoice_discount_amount', 10, 2)->default(0);
            $table->decimal('tax_total', 10, 2)->default(0);
            $table->decimal('delivery_charge', 10, 2)->default(0);
            $table->decimal('grand_total', 10, 2)->default(0);
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->unsignedBigInteger('cashbook_id')->nullable();
            $table->timestamps();

            $table->foreign('branch_id')->references('id')->on('branches')->onDelete('cascade');
            $table->foreign('inventory_id')->references('id')->on('inventories')->onDelete('cascade');
            $table->foreign('customer_id')->references('id')->on('customers')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
            $table->foreign('selling_price_group_id')->references('id')->on('selling_price_groups')->onDelete('cascade');
            $table->foreign('sell_tax_id')->references('id')->on('taxs')->onDelete('cascade');
            $table->foreign('cashbook_id')->references('id')->on('cashbooks')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sale_invoices');
    }
};
