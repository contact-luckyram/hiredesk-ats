<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Seeds a complete demo company: team, workflows, jobs, candidates and new hires. */
    public function run(): void
    {
        $this->call([
            SetupSeeder::class,
            RecruitingSeeder::class,
        ]);
    }
}
