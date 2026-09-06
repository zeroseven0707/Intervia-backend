<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        // Positions & Skills
        $this->call(PositionSkillSeeder::class);

        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@intervia.app'],
            [
                'name'     => 'Admin',
                'password' => bcrypt('password'),
                'role'     => UserRole::Admin,
            ]
        );

        // Demo user
        User::firstOrCreate(
            ['email' => 'demo@intervia.app'],
            [
                'name'             => 'Demo User',
                'password'         => bcrypt('password'),
                'role'             => UserRole::User,
                'experience_level' => 'mid',
            ]
        );
    }
}
