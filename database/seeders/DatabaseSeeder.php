<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Modules\SuperAdmin\Database\Seeders\SuperAdminDatabaseSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionFeatureSeeder;
use Modules\Subscription\Database\Seeders\SubscriptionPlanSeeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            SuperAdminDatabaseSeeder::class,
            SubscriptionFeatureSeeder::class,
            SubscriptionPlanSeeder::class,
        ]);

    }
}
