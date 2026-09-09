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
        // 1. Positions + Skills (+ attach importance)
        $this->call(PositionSkillSeeder::class);

        // 2. User credentials
        $admin = User::firstOrCreate(
            ['email' => 'admin@intervia.app'],
            [
                'name'             => 'Admin Intervia',
                'password'         => bcrypt('admin123'),
                'role'             => UserRole::Admin,
                'experience_level' => 'senior',
            ]
        );

        $targetPm = \App\Models\Position::whereSlug('product-manager')->first();

        $user = User::firstOrCreate(
            ['email' => 'user@intervia.app'],
            [
                'name'                  => 'Budi Santoso',
                'password'              => bcrypt('user123'),
                'role'                  => UserRole::User,
                'experience_level'      => 'mid',
                'target_position_id'    => $targetPm?->id,
            ]
        );

        // 3. Learning content (sources, topics, materials + attach skills)
        $this->call(LearningContentSeeder::class);

        // 4. Payment settings + packages + transactions + default AI prompts
        $this->call(DemoPaymentSeeder::class);

        // 5. Sample interview history + reports + user skill scores (untuk demo user)
        $this->call(DemoInterviewDataSeeder::class);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════╗');
        $this->command->info('║  🚀 SEMUA SEEDER BERHASIL DIJALANKAN!      ║');
        $this->command->info('╚══════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('Credentials Demo:');
        $this->command->info("  🟥 ADMIN: admin@intervia.app / admin123  → /admin");
        $this->command->info("  🟦 USER : user@intervia.app  / user123   → /dashboard");
        $this->command->info('');
    }
}
