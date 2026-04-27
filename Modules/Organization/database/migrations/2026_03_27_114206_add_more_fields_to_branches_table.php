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
        Schema::table('branches', function (Blueprint $table) {
            $table->string('address')->after('name');
            $table->decimal('latitude', 10, 7)
                ->nullable()
                ->after('address');

            $table->decimal('longitude', 10, 7)
                ->nullable()
                ->after('latitude');

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

            $table->json('mobile_phones')
                ->nullable()
                ->after('state_id');

            $table->string('email')
                ->nullable()
                ->after('mobile_phones');

            $table->string('website')
                ->nullable()
                ->after('email');

            // new facebook field
            $table->string('facebook')
                ->nullable()
                ->after('website');
        });
    }

    public function down(): void
    {
        Schema::table('branches', function (Blueprint $table) {

            $table->dropForeign(['city_id']);
            $table->dropForeign(['state_id']);
            $table->dropForeign(['default_selling_price_group_id']);

            $table->dropColumn([
                'address',
                'latitude',
                'longitude',
                'city_id',
                'state_id',
                'mobile_phones',
                'email',
                'website',
                'facebook'
            ]);
        });
    }
};
