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
                'name'             => 'Admin Intervia',
                'password'         => bcrypt('admin123'),
                'role'             => UserRole::Admin,
                'experience_level' => 'senior',
            ]
        );

        // Demo user
        User::firstOrCreate(
            ['email' => 'user@intervia.app'],
            [
                'name'             => 'Budi Santoso',
                'password'         => bcrypt('user123'),
                'role'             => UserRole::User,
                'experience_level' => 'mid',
            ]
        );
    }
}
