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
        Schema::create('goods_receive_note_charges', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('goods_receive_note_id');
            $table->enum('charge_type', ['cargo', 'delivery', 'other'])->default('other');
            $table->unsignedBigInteger('currency_id');
            $table->decimal('amount', 15, 2);
            $table->decimal('base_amount', 15, 2);
            $table->string('description')->nullable();
            $table->timestamps();

            $table->foreign('goods_receive_note_id')->references('id')->on('goods_receive_notes')->onDelete('cascade');
            $table->foreign('currency_id')->references('id')->on('currencies')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('goods_receive_note_charges');
    }
};
