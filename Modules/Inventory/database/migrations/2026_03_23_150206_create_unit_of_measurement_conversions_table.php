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
        Schema::create('unit_of_measurement_conversions', function (Blueprint $table) {
            $table->id();
            $table->string('conversions_name');
            $table->foreignId('base_unit_id')
                ->constrained('unit_of_measurements')
                ->cascadeOnDelete();

            $table->foreignId('conversion_unit_id')
                ->constrained('unit_of_measurements')
                ->cascadeOnDelete();

            $table->decimal('conversion_rate', 18, 6);

            $table->enum('status', ['active', 'inactive']);

            $table->foreignId('created_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->foreignId('updated_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();

            $table->timestamps();
            $table->softDeletes();

            $table->unique('conversions_name');
            
            // $table->unique(['base_unit_id', 'conversion_unit_id'], 'uom_conversion_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('unit_of_measurement_conversions');
    }
};
