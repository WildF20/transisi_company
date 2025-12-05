<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'admin transisi',
            'email' => 'admin@transisi.id',
            'password' => bcrypt('transisi'),
        ]);

        User::factory()->create([
            'name' => 'superadmin',
            'email' => 'superadmin@transisi.id',
            'password' => bcrypt('superadmin'),
        ]);
    }
}
