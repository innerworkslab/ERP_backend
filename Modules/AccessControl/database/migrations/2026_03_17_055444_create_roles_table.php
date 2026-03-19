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
        Schema::create('roles', function (Blueprint $table) {
            $table->id();

            $table->string('name');

            $table->foreignId('parent_role_id')
                ->nullable()
                ->constrained('roles')
                ->nullOnDelete();

            $table->foreignId('branch_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('department_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            // $table->boolean('is_super')->default(false);

            $table->enum('status', ['active', 'inactive']);

            $table->timestamps();

            $table->unique(['name', 'branch_id', 'department_id'], 'roles_name_branch_department_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('roles');
    }
};
