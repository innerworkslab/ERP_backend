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
        Schema::create('cashbook_transaction_attachments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('cashbook_transaction_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->string('file_name');

            $table->string('file_path');

            $table->string('file_type')->nullable();

            $table->string('extension')->nullable();

            $table->enum('attachment_type', [
                'image',
                'pdf',
                'spreadsheet',
                'document',
                'other',
            ])->nullable();

            $table->unsignedBigInteger('file_size')->nullable();

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cashbook_transaction_attachments');
    }
};
