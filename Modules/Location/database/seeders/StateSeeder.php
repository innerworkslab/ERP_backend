<?php

namespace Modules\Location\database\seeders;

use Illuminate\Database\Seeder;
use Modules\Location\app\Models\State;

class StateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        State::create([
            "id" => 1,
            "name" => "Ayeyarwady Region"
        ]);
        State::create([
            "id" => 2,
            "name" => "Bago Region"
        ]);
        State::create([
            "id" => 3,
            "name" => "Chin State"
        ]);
        State::create([
            "id" => 4,
            "name" => "Kachin State"
        ]);
        State::create([
            "id" => 5,
            "name" => "Kayah State"
        ]);
        State::create([
            "id" => 6,
            "name" => "Kayin State"
        ]);
        State::create([
            "id" => 7,
            "name" => "Magway Region"
        ]);
        State::create([
            "id" => 8,
            "name" => "Mandalay Region"
        ]);
        State::create([
            "id" => 9,
            "name" => "Mon State"
        ]);
        State::create([
            "id" => 10,
            "name" => "Naypyidaw Union Territory"
        ]);
        State::create([
            "id" => 11,
            "name" => "Rakhine State"
        ]);
        State::create([
            "id" => 12,
            "name" => "Sagaing Region"
        ]);
        State::create([
            "id" => 13,
            "name" => "Shan State"
        ]);
        State::create([
            "id" => 14,
            "name" => "Tanintharyi Region"
        ]);
        State::create([
            "id" => 15,
            "name" => "Yangon Region"
        ]);
    }
}
