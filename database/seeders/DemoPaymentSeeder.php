<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Package;
use App\Models\PaymentSetting;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;

class DemoPaymentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('=== Demo Payment Seeder ===');

        // ── 1. Payment Settings Default ─────────────────────────────────────
        $settings = PaymentSetting::getSettings();
        $settings->update([
            'midtrans_merchant_id'             => env('MIDTRANS_MERCHANT_ID', 'G000000001'),
            'midtrans_server_key'              => env('MIDTRANS_SERVER_KEY', ''),
            'midtrans_client_key'              => env('MIDTRANS_CLIENT_KEY', ''),
            'midtrans_production'              => (bool) env('MIDTRANS_PRODUCTION', false),
            'default_single_session_price'     => 3000,
            'require_payment'                  => true,
            'free_trial_sessions'              => 1,
        ]);
        $this->command->info('✓ Payment settings di-set (single session: Rp 3.000, free trial: 1 sesi)');

        // ── 2. Sample Packages ─────────────────────────────────────────────
        $packages = $this->packages();

        foreach ($packages as $pkg) {
            Package::updateOrCreate(
                ['slug' => $pkg['slug']],
                $pkg
            );
        }
        $this->command->info('✓ ' . count($packages) . ' paket harga dibuat/updated');

        // ── 3. Sample Credit & Transactions untuk Demo User ────────────────
        $admin = User::where('email', 'admin@intervia.app')->first();
        $user  = User::where('email', 'user@intervia.app')->first();

        if ($admin) {
            $admin->update([
                'credit_sessions'  => 999,
                'subscribed_until' => now()->addYears(10),
            ]);
            $this->command->info('✓ Admin dikasih 999 credit + subscription sampai 10 tahun lagi');
        }

        if ($user) {
            $user->update([
                'credit_sessions'  => 3,
                'subscribed_until' => null,
            ]);
            $this->command->info('✓ Demo user (Budi Santoso) dikasih 3 sesi kredit untuk testing');

            // Sample SINGLE SESSION transaction (pending)
            $txPending = Transaction::firstOrCreate(
                ['order_id' => 'INV-DEMO-PENDING-001'],
                [
                    'user_id'        => $user->id,
                    'package_id'     => null,
                    'package_name'   => 'Single Session Interview',
                    'gross_amount'   => 3000,
                    'transaction_status' => 'pending',
                    'payment_type'   => null,
                    'payment_link'   => 'https://app.sandbox.midtrans.com/snap/v2/vtweb/DEMO-PENDING',
                    'expires_at'     => now()->addHours(6),
                ]
            );
            if ($txPending->wasRecentlyCreated) {
                $this->command->info('  → Sample transaksi PENDING dibuat (Single Rp 3.000)');
            }

            // Sample transaction (SETTLEMENT / sudah dibayar)
            $packageS10 = Package::where('slug', 'starter-10-sessions')->first();
            $txPaid = Transaction::firstOrCreate(
                ['order_id' => 'INV-DEMO-SETTLED-001'],
                [
                    'user_id'                 => $user->id,
                    'package_id'              => $packageS10?->id,
                    'package_name'            => $packageS10?->name ?? 'Starter 10 Sessions',
                    'gross_amount'            => 25000,
                    'transaction_status'      => 'settlement',
                    'payment_type'            => 'bank_transfer',
                    'midtrans_transaction_id' => 'DEMO-MIDTRANS-SETTLED-001',
                    'fraud_status'            => 'accept',
                    'paid_at'                 => now()->subDays(3),
                    'expires_at'              => now()->addHours(6),
                    'raw_response'            => [
                        'order_id'           => 'INV-DEMO-SETTLED-001',
                        'transaction_status' => 'settlement',
                        'payment_type'       => 'bank_transfer',
                        'bank'               => 'bca',
                        'gross_amount'       => '25000.00',
                    ],
                ]
            );
            if ($txPaid->wasRecentlyCreated) {
                $this->command->info('  → Sample transaksi SETTLED dibuat (Rp 25.000, BCA VA)');
            }

            // Sample transaction EXPIRED
            $txExpired = Transaction::firstOrCreate(
                ['order_id' => 'INV-DEMO-EXPIRED-001'],
                [
                    'user_id'            => $user->id,
                    'package_id'         => null,
                    'package_name'       => 'Single Session Interview',
                    'gross_amount'       => 3000,
                    'transaction_status' => 'expire',
                    'payment_type'       => 'gopay',
                    'paid_at'            => null,
                    'expires_at'         => now()->subDays(2),
                ]
            );
            if ($txExpired->wasRecentlyCreated) {
                $this->command->info('  → Sample transaksi EXPIRED dibuat (GoPay)');
            }
        }

        // ── 4. Sample AiPromptTemplates supaya semua type ada di DB ────────
        try {
            $now = now();
            $prompts = [
                [
                    'name'          => 'Question Generator Default',
                    'type'          => 'question_generator',
                    'version'       => '1.0',
                    'system_prompt' => 'You are a professional technical interviewer. Generate one focused interview question. Respond with valid JSON only — no markdown, no explanation. Never repeat questions already asked.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Answer Evaluator Default',
                    'type'          => 'answer_evaluator',
                    'version'       => '1.0',
                    'system_prompt' => 'You are a senior technical interviewer and expert evaluator. Evaluate interview answers fairly and constructively. Do NOT reward keyword stuffing — evaluate actual understanding. Respond with valid JSON only — no markdown, no explanation.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Job Analyzer Default',
                    'type'          => 'job_analyzer',
                    'version'       => '1.0',
                    'system_prompt' => 'Extract structured data from job descriptions. Respond with valid JSON only.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Career Coach Default',
                    'type'          => 'career_coach',
                    'version'       => '1.0',
                    'system_prompt' => 'You are a supportive career coach. Give a brief, actionable, encouraging message. Under 150 words. Be specific and honest — not generic.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Skill Gap Default',
                    'type'          => 'skill_gap',
                    'version'       => '1.0',
                    'system_prompt' => 'Identify skill gaps between user current level and target. Respond with valid JSON only.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Learning Recommendation Default',
                    'type'          => 'recommender',
                    'version'       => '1.0',
                    'system_prompt' => 'Recommend learning resources based on weak skills and target position. Respond with valid JSON only.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Interviewer Persona',
                    'type'          => 'interviewer',
                    'version'       => '1.0',
                    'system_prompt' => 'You are a fair, professional technical interviewer.',
                    'is_active'     => true,
                ],
                [
                    'name'          => 'Evaluator Persona',
                    'type'          => 'evaluator',
                    'version'       => '1.0',
                    'system_prompt' => 'You are an expert evaluator.',
                    'is_active'     => true,
                ],
            ];

            $countCreated = 0;
            foreach ($prompts as $prompt) {
                $exists = \App\Models\AiPromptTemplate::where('type', $prompt['type'])
                    ->where('is_active', true)
                    ->exists();
                if (!$exists) {
                    \App\Models\AiPromptTemplate::create($prompt + [
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                    $countCreated++;
                }
            }
            $this->command->info("✓ {$countCreated} AiPromptTemplate defaults dibuat");
        } catch (\Throwable $e) {
            $this->command->warn('⚠ Gagal seed prompt templates: ' . $e->getMessage());
        }

        $this->command->info('=== Demo Payment Seeder Selesai ===');
        $this->command->info('');
        $this->command->info('CREDENTIAL DEMO:');
        $this->command->info('  Admin: admin@intervia.app / admin123');
        $this->command->info('  User : user@intervia.app / user123  (3 credit sesi + sample transaksi)');
        $this->command->info('');
        $this->command->info('CATATAN: Midtrans keys masih KOSONG.');
        $this->command->info('  Set via API PUT /api/v1/admin/payment/settings');
    }

    private function packages(): array
    {
        return [
            [
                'name'             => 'Coba 1 Kali',
                'slug'             => 'single-1-session',
                'description'      => 'Bayar sekali untuk 1 sesi interview lengkap beserta evaluasi AI.',
                'type'             => 'session',
                'session_count'    => 1,
                'duration_days'    => null,
                'price'            => 3000,
                'discounted_price' => null,
                'is_active'        => true,
                'is_popular'       => false,
                'sort_order'       => 1,
                'features'         => [
                    '1 x Interview Lengkap',
                    'Skor + Feedback AI',
                    'Rekomendasi Belajar',
                    'Exp: 12 bulan',
                ],
            ],
            [
                'name'             => 'Starter Pack',
                'slug'             => 'starter-10-sessions',
                'description'      => 'Hemat 17%! Cocok untuk yang ingin latihan beberapa kali sebelum interview real.',
                'type'             => 'session',
                'session_count'    => 10,
                'duration_days'    => null,
                'price'            => 30000,
                'discounted_price' => 25000,
                'is_active'        => true,
                'is_popular'       => true,
                'sort_order'       => 2,
                'features'         => [
                    '10 x Interview',
                    'Skor + Feedback AI',
                    'Rekomendasi Belajar',
                    'Progress Tracker',
                    'Hemat Rp 5.000',
                    'Exp: 12 bulan',
                ],
            ],
            [
                'name'             => 'Pro Pack',
                'slug'             => 'pro-30-sessions',
                'description'      => 'Paling hemat untuk persiapan intensif. Latihan 30x full interview.',
                'type'             => 'session',
                'session_count'    => 30,
                'duration_days'    => null,
                'price'            => 90000,
                'discounted_price' => 65000,
                'is_active'        => true,
                'is_popular'       => false,
                'sort_order'       => 3,
                'features'         => [
                    '30 x Interview',
                    'All Starter Features',
                    'Skill Gap Analysis',
                    'Priority AI Queue',
                    'Hemat Rp 25.000',
                ],
            ],
            [
                'name'             => 'Subscription Bulanan',
                'slug'             => 'subs-monthly',
                'description'      => 'Unlimited interview selama 30 hari! Cocok untuk bootcamp / jobseeker aktif.',
                'type'             => 'subscription',
                'session_count'    => null,
                'duration_days'    => 30,
                'price'            => 99000,
                'discounted_price' => 79000,
                'is_active'        => true,
                'is_popular'       => true,
                'sort_order'       => 4,
                'features'         => [
                    'Unlimited Interview',
                    'All Pro Features',
                    'Skill Dashboard + Trend',
                    'Export PDF Report',
                    'Akses Materi Premium',
                ],
            ],
            [
                'name'             => 'Subscription Tahunan',
                'slug'             => 'subs-yearly',
                'description'      => 'Untuk profesional yang serius meningkatkan kemampuan interview 1 tahun penuh.',
                'type'             => 'subscription',
                'session_count'    => null,
                'duration_days'    => 365,
                'price'            => 990000,
                'discounted_price' => 699000,
                'is_active'        => true,
                'is_popular'       => false,
                'sort_order'       => 5,
                'features'         => [
                    'Unlimited 365 Hari',
                    'Semua Fitur Monthly',
                    'Discount 30%',
                    '1-on-1 Career Review',
                    'Support Prioritas',
                ],
            ],
        ];
    }
}
