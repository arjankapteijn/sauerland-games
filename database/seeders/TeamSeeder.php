<?php

namespace Database\Seeders;

use App\Models\Team;
use Illuminate\Database\Seeder;

class TeamSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Team::query()->firstOrCreate(['name' => 'Rood'], ['color' => '#AE4A2C']);
        Team::query()->firstOrCreate(['name' => 'Blauw'], ['color' => '#33566F']);
    }
}
