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
        Schema::table('branches', function (Blueprint $table) {

            $table->decimal('latitude', 10, 7)->nullable()->after('name');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');

            $table->foreignId('city_id')
                ->nullable()
                ->after('longitude')
                ->constrained('cities')
                ->nullOnDelete();

            $table->foreignId('state_id')
                ->nullable()
                ->after('city_id')
                ->constrained('states')
                ->nullOnDelete();

            $table->string('mobile')->nullable()->after('state_id');
            $table->string('alternate_phone')->nullable()->after('mobile');
            $table->string('email')->nullable()->after('alternate_phone');
            $table->string('website')->nullable()->after('email');

            $table->foreignId('default_selling_price_group_id')
                ->nullable()
                ->after('website')
                ->constrained('selling_price_groups')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {

            $table->dropForeign(['city_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['default_selling_price_group_id']);

            $table->dropColumn([
                'latitude',
                'longitude',
                'city_id',
                'state_id',
                'mobile',
                'alternate_phone',
                'email',
                'website',
                'default_selling_price_group_id',
            ]);
        });
    }
};
