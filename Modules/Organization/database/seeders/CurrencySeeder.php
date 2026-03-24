<?php

namespace Modules\Organization\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Organization\app\Models\Currency;

class CurrencySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currencies = [
            [
                'name' => 'US Dollar',
                'code' => 'USD',
                'symbol' => '$',
                'exchange_rate' => 0.0005,
                'is_base_currency' => false,
                'last_exchange_rate_update' => now()->toDateString(),
            ]
        ];

        foreach ($currencies as $currency) {
            Currency::updateOrCreate(
                ['code' => $currency['code']],
                $currency
            );
        }
    }
}
