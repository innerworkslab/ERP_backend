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
        Schema::create('staff_personal_informations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id')->nullable()->unique();
            $table->date('date_of_birth')->nullable();
            $table->string('nrc_number', 50)->nullable()->unique();
            $table->string('nrc_image', 150)->nullable();
            $table->string('nrc_image_path', 255)->nullable();
            $table->string('nrc_image_url', 255)->nullable();
            
            $table->string('father_name', 20)->nullable();
            $table->string('mother_name', 20)->nullable();

            $table->string('town', 20)->nullable();
            $table->string('township', 20)->nullable();
            $table->string('address', 255)->nullable();

            $table->string('house_hold_information_image', 150)->nullable();
            $table->string('house_hold_information_image_path', 255)->nullable();
            $table->string('house_hold_information_image_url', 255)->nullable();

            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');

            $table->unique(['user_id', 'nrc_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_personal_informations');
    }
};
