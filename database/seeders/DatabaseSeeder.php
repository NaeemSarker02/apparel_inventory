<?php

namespace Database\Seeders;

use Database\Seeders\InventorySystemSeeder;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            InventorySystemSeeder::class,
        ]);
    }
}
