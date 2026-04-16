<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * Note: Only ComprehensiveWebsiteSeeder is called here to avoid duplicate
     * questions being created by multiple seeders. All question creation logic
     * has been consolidated into ComprehensiveWebsiteSeeder with proper
     * duplicate checking.
     */
    public function run(): void
    {
        $this->call([
            ComprehensiveWebsiteSeeder::class,
            UserSeeder::class,
            DemoUserWeakTopicsSeeder::class,
            CleanLeaderboardSeeder::class,
        ]);

        // Note: Other seeders like QuestionSeeder and ComprehensiveTestSeeder
        // are NOT called here to prevent duplicate question creation.
        // If you need to run them separately, ensure they don't conflict
        // with the main seeder.
    }
}
