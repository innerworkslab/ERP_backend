<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('journal_entries', function (Blueprint $table) {
            $table->id();

            $table->dateTime('journal_datetime');

            $table->string('source_type')->nullable();

            $table->unsignedBigInteger('source_id')->nullable();

            $table->text('description')->nullable();


            $table->timestamps();

            $table->index([
                'source_type',
                'source_id',
            ]);

            $table->index([
                'journal_date'
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('journal_entries');
    }
};
