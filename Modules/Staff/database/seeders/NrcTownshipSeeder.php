<?php

namespace Modules\Staff\database\seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Modules\Staff\app\Models\NrcTownship;

class NrcTownshipSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $jsonPath = module_path('Staff', 'nrc_townships.json');
        if (!is_file($jsonPath)) {
            throw new \RuntimeException("NRC township json file not found at: {$jsonPath}");
        }

        $townshipJson = file_get_contents($jsonPath);
        $townships = json_decode($townshipJson, true);
        if (json_last_error() !== JSON_ERROR_NONE || !isset($townships['data']) || !is_array($townships['data'])) {
            throw new \RuntimeException('Invalid NRC township json format.');
        }

        $now = Carbon::now();
        $townshipData = array_map(function ($t) use ($now) {
            return [
                'id' => (int) $t['id'],
                'name_en' => $t['name_en'],
                'name_mm' => $t['name_mm'],
                'nrc_code' => (string) $t['nrc_code'],
                'created_at' => $t['created_at'] ?? $now,
                'updated_at' => $t['updated_at'] ?? $now,
            ];
        }, $townships['data']);

        NrcTownship::query()->upsert(
            $townshipData,
            ['id'],
            ['name_en', 'name_mm', 'nrc_code', 'updated_at']
        );
    }
}
