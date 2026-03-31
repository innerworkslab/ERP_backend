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
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('sku')->unique();
            $table->string('image')->nullable();
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->unsignedBigInteger('category_id');
            $table->unsignedBigInteger('brand_id');
            $table->decimal('alert_quantity', 10, 2)->default(0);
            //purchase
            $table->decimal('purchase_price', 10, 2)->default(0);
            $table->unsignedBigInteger('purchase_currency_id')->nullable();
            $table->unsignedBigInteger('purchase_tax_id')->nullable();
            $table->unsignedBigInteger('purchase_uom_id')->nullable();

            //sale
            $table->decimal('sale_price', 10, 2)->default(0);
            $table->unsignedBigInteger('sale_currency_id')->nullable();
            $table->unsignedBigInteger('sale_tax_id')->nullable();
            $table->unsignedBigInteger('sale_uom_id')->nullable();

            //country
            $table->unsignedBigInteger('origin_country_id')->nullable();

            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();

            $table->enum('status', ['active', 'inactive'])->default('active');
            $table->timestamps();

            $table->foreign('category_id')->references('id')->on('categories')->onDelete('cascade');
            $table->foreign('brand_id')->references('id')->on('brands')->onDelete('cascade');
            $table->foreign('purchase_currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('purchase_tax_id')->references('id')->on('taxs')->onDelete('set null');
            $table->foreign('purchase_uom_id')->references('id')->on('unit_of_measurements')->onDelete('set null');
            $table->foreign('sale_currency_id')->references('id')->on('currencies')->onDelete('set null');
            $table->foreign('sale_tax_id')->references('id')->on('taxs')->onDelete('set null');
            $table->foreign('sale_uom_id')->references('id')->on('unit_of_measurements')->onDelete('set null');
            $table->foreign('origin_country_id')->references('id')->on('origin_countries')->onDelete('set null');   
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
