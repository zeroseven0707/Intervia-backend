<?php

namespace Database\Seeders;

use App\Enums\InterviewStatus;
use App\Enums\InterviewMode;
use App\Models\InterviewQuestion;
use App\Models\InterviewReport;
use App\Models\InterviewSession;
use App\Models\InterviewAnswer;
use App\Models\AnswerEvaluation;
use App\Models\Position;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserSkillScore;
use Illuminate\Database\Seeder;

class DemoInterviewDataSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('--- Demo Interview Data Seeder ---');

        $user = User::where('email', 'user@intervia.app')->first();
        if (!$user) {
            $this->command->warn('User demo tidak ada, skip interview data.');
            return;
        }

        $pm   = Position::whereSlug('product-manager')->first();
        $fe   = Position::whereSlug('frontend-developer')->first();
        $da   = Position::whereSlug('data-analyst')->first();
        $qa   = Position::whereSlug('quality-assurance-qa')->first();
        $ux   = Position::whereSlug('ui-ux-designer')->first();

        $sessions = [];

        // ── 1. COMPLETED: Product Manager ──────────────────────────────
        if ($pm) $sessions[] = $this->createCompleted($user, $pm, InterviewMode::Simulation, 7, 72, [
            'Product Strategy'        => ['score' => 78, 'weight' => 30, 'insight' => 'Bagus mengerti konsep, tapi kurang bukti data.'],
            'Prioritization'          => ['score' => 64, 'weight' => 25, 'insight' => 'Paham RICE tapi belum bisa membela score.'],
            'User Research'           => ['score' => 70, 'weight' => 20, 'insight' => 'Tipe riset paham tapi bingung prioritaskan.'],
            'Stakeholder Management'  => ['score' => 80, 'weight' => 15, 'insight' => 'Kuat! Sudah natural.'],
            'Data Analysis'           => ['score' => 60, 'weight' => 10, 'insight' => 'SQL perlu digenjot.'],
        ]);

        // ── 2. COMPLETED: Frontend Dev ─────────────────────────────────
        if ($fe) $sessions[] = $this->createCompleted($user, $fe, InterviewMode::Simulation, 6, 66, [
            'React'        => ['score' => 72, 'weight' => 35, 'insight' => 'Dasar hooks paham, tapi useEffect infinite loop kurang peka.'],
            'JavaScript'   => ['score' => 75, 'weight' => 25, 'insight' => 'Promise, async-await bagus, closure kadang lupa.'],
            'TypeScript'   => ['score' => 58, 'weight' => 20, 'insight' => 'Generics dan utility types lemah.'],
            'CSS'          => ['score' => 60, 'weight' => 15, 'insight' => 'Flex paham, grid dan responsive perlu latihan.'],
            'Git'          => ['score' => 82, 'weight' => 5,  'insight' => 'Sangat lancar.'],
        ]);

        // ── 3. COMPLETED: Data Analyst ─────────────────────────────────
        if ($da) $sessions[] = $this->createCompleted($user, $da, InterviewMode::Challenging, 5, 58, [
            'SQL'                => ['score' => 52, 'weight' => 40, 'insight' => 'JOIN 3+ table sering salah. Perbanyak latihan soal.'],
            'Python'             => ['score' => 65, 'weight' => 25, 'insight' => 'Loop & list ok, pandas groupby perlu diperdalam.'],
            'Statistics'         => ['score' => 55, 'weight' => 20, 'insight' => 'Distribusi & hipotesis testing lemah.'],
            'Data Visualization' => ['score' => 68, 'weight' => 15, 'insight' => 'Pilih chart udah tepat, narasi kurang.'],
        ]);

        // ── 4. INTERVIEWING: QA ────────────────────────────────────────
        if ($qa) $sessions[] = $this->createInterviewing($user, $qa, InterviewMode::Simulation, 5, 2);

        // ── 5. COMPLETED: UI/UX Designer ───────────────────────────────
        if ($ux) $sessions[] = $this->createCompleted($user, $ux, InterviewMode::Simulation, 6, 76, [
            'Figma'           => ['score' => 85, 'weight' => 30, 'insight' => 'Sangat ahli. Component rapih, auto-layout on point.'],
            'User Research'   => ['score' => 72, 'weight' => 25, 'insight' => 'Bagus memformulasikan pertanyaan riset.'],
            'Prototyping'     => ['score' => 80, 'weight' => 20, 'insight' => 'Interaction flow jelas, variasi state lengkap.'],
            'Design Systems'  => ['score' => 68, 'weight' => 15, 'insight' => 'Naming token dan hierarchy bisa lebih disiplin.'],
            'Wireframing'     => ['score' => 78, 'weight' => 10, 'insight' => 'Sangat cepat dan akurat.'],
        ]);

        // ── 6. FAILED: Old Product Manager (cuma untuk tampilan) ───────
        if ($pm) $sessions[] = $this->createFailed($user, $pm, InterviewMode::Practice, 5, now()->subDays(30));

        // ── 7. PENDING (baru dibuat, untuk tampilan history) ───────────
        if ($fe) $sessions[] = $this->createPending($user, $fe, InterviewMode::Practice, 8);

        $this->command->info('✓ ' . count($sessions) . " interview sessions dibuat untuk {$user->name}");

        // ── 8. User Progress (trend skor kemajuan) ─────────────────────
        if ($pm && $da) {
            $sqlSkill = \App\Models\Skill::whereSlug('sql')->first()
                         ?? \App\Models\Skill::whereSlug('data-analysis')->first()
                         ?? \App\Models\Skill::first();

            if ($sqlSkill) {
                UserProgress::create([
                    'user_id'          => $user->id,
                    'skill_id'         => $sqlSkill->id,
                    'previous_score'   => 0,
                    'current_score'    => 60,
                    'improvement'      => 60,
                    'last_assessed_at' => now()->subDays(20),
                ]);

                UserProgress::create([
                    'user_id'          => $user->id,
                    'skill_id'         => $sqlSkill->id,
                    'previous_score'   => 60,
                    'current_score'    => 52,
                    'improvement'      => -8,
                    'last_assessed_at' => now()->subDays(5),
                ]);
                $this->command->info('✓ UserProgress sample dibuat (trend skor kemajuan)');
            }
        }
    }

    private function createCompleted(
        User $user, Position $position, InterviewMode $mode, int $questions, int $overall, array $skills
    ): InterviewSession {
        $startedAt = now()->subDays(rand(2, 15))->subMinutes(rand(10, 80));

        $session = InterviewSession::create([
            'user_id'         => $user->id,
            'position_id'     => $position->id,
            'mode'            => $mode,
            'difficulty'      => 'adaptive',
            'question_count'  => $questions,
            'status'          => InterviewStatus::Completed,
            'overall_score'   => $overall,
            'started_at'      => $startedAt,
            'completed_at'    => $startedAt->clone()->addMinutes(rand(20, 90)),
        ]);

        // ── Create questions + answers + evaluations ──────────────────
        $questionSamples = [
            "Beri tahu saya tentang proyek yang paling kamu banggakan dan peranmu di sana.",
            "Bagaimana cara kamu memprioritaskan fitur ketika resource terbatas?",
            "Ceritakan saat kamu harus membuat keputusan sulit tanpa data yang lengkap.",
            "Apa kelemahan terbesar yang kamu akui dan bagaimana kamu mengatasinya?",
            "Bagaimana kamu berkomunikasi dengan tim engineering yang tidak setuju dengan rencanamu?",
            "Jelaskan sebuah konsep teknis secara sederhana seperti bicara ke anak SMA.",
            "Kesalahan apa yang pernah kamu buat di pekerjaan? Apa pelajarannya?",
            "Di mana kamu melihat dirimu 5 tahun lagi?",
        ];
        shuffle($questionSamples);

        $cats     = ['technical', 'behavioral', 'project_experience', 'cultural', 'problem_solving'];
        $diffs    = ['easy', 'medium', 'hard'];
        $sequence = 1;

        foreach (array_slice($questionSamples, 0, $questions) as $q) {
            $skillNames = array_keys($skills);
            $skillName  = $skillNames[array_rand($skillNames)];
            $skill = \App\Models\Skill::whereSlug(\Illuminate\Support\Str::slug($skillName))->first();

            $question = InterviewQuestion::create([
                'session_id'    => $session->id,
                'sequence'      => $sequence++,
                'question_text' => $q,
                'category'      => $cats[array_rand($cats)],
                'difficulty'    => $diffs[array_rand($diffs)],
                'skill'         => $skillName,
                'skill_id'      => $skill?->id,
                'created_at'    => $startedAt->clone()->addMinutes($sequence * 6),
            ]);

            $userAnswer = 'Jawaban kandidat demo untuk pertanyaan: "' . substr($q, 0, 40) . '...". Di proyek sebelumnya saya mengerjakan sesuai timeline dan berkomunikasi efektif dengan stakeholder.';

            $answer = InterviewAnswer::create([
                'question_id'       => $question->id,
                'answer_text'       => $userAnswer,
                'submitted_at'      => $question->created_at?->clone()->addMinutes(rand(2, 9)),
                'processing_status' => 'done',
            ]);

            $score = rand(55, 85);
            AnswerEvaluation::create([
                'answer_id'          => $answer->id,
                'overall_score'      => $score,
                'relevance_score'    => max(1, min(100, $score + rand(-5, 5))),
                'knowledge_score'    => max(1, min(100, $score + rand(-5, 5))),
                'clarity_score'      => max(1, min(100, $score + rand(-5, 5))),
                'completeness_score' => max(1, min(100, $score + rand(-5, 5))),
                'reasoning_score'    => max(1, min(100, $score + rand(-5, 5))),
                'strengths'          => ['Jawaban terstruktur', 'Ada contoh konkrit', 'Logika berjalur'],
                'weaknesses'         => ['Kurang angka/spesifik', 'STAR format belum rapi'],
                'missing_points'     => ['Metrik hasil', 'Timeline dan hambatan', 'Alternatif solusi'],
                'improvement_advice' => 'Gunakan format STAR. Sebutkan metrik kuantitatif (persen peningkatan, waktu penghematan). Jelaskan hambatan dan cara kamu atasi.',
                'example_answer'     => 'Contoh jawaban ideal: Di proyek X saya memimpin fitur Y selama 3 bulan, meningkatkan konversi 23% walau ada hambatan perubahan scope dari klien, yang saya atasi dengan...',
                'created_at'         => $answer->submitted_at?->clone()->addSeconds(3),
            ]);
        }

        // ── Create Skill Scores ────────────────────────────────────────
        foreach ($skills as $skillName => $s) {
            $skill = \App\Models\Skill::whereSlug(\Illuminate\Support\Str::slug($skillName))->first();
            if (!$skill) {
                $skill = \App\Models\Skill::firstOrCreate(
                    ['slug' => \Illuminate\Support\Str::slug($skillName)],
                    ['name' => $skillName, 'category' => 'general']
                );
            }

            UserSkillScore::updateOrCreate(
                ['user_id' => $user->id, 'skill_id' => $skill->id],
                [
                    'score'              => $s['score'],
                    'confidence'         => min(1.0, max(0.1, rand(60, 95) / 100)),
                    'source_session_id'  => $session->id,
                    'updated_at'         => $session->completed_at,
                ]
            );
        }

        // ── Create Report ──────────────────────────────────────────────
        $skillBreakdown = [];
        foreach ($skills as $skillName => $s) {
            $skillBreakdown[] = [
                'name'    => $skillName,
                'score'   => $s['score'],
                'weight'  => $s['weight'],
                'insight' => $s['insight'],
                'level'   => $s['score'] >= 80 ? 'expert' : ($s['score'] >= 65 ? 'proficient' : ($s['score'] >= 40 ? 'developing' : 'beginner')),
            ];
        }

        $weakest   = collect($skillBreakdown)->sortBy('score')->take(3)->pluck('name')->values()->toArray();
        $strongest = collect($skillBreakdown)->sortByDesc('score')->take(3)->pluck('name')->values()->toArray();

        $recommendationCount = min(3, count($weakest));
        $recommendations = [];
        for ($i = 0; $i < $recommendationCount; $i++) {
            $recommendations[] = [
                'skill'       => $weakest[$i],
                'reason'      => "Skor masih di bawah target. Fokus area ini untuk kenaikan tercepat.",
                'difficulty'  => 'beginner',
                'materials'   => [],
            ];
        }

        // Calculate rough sub-scores from skills
        $commAvg   = in_array('Stakeholder Management', array_keys($skills)) ? ($skills['Stakeholder Management']['score'] ?? 70) : 70;
        $techNames = ['React','JavaScript','TypeScript','CSS','Git','SQL','Python','Statistics','Figma','Prototyping','Design Systems'];
        $techScores = [];
        foreach ($techNames as $tn) if (isset($skills[$tn])) $techScores[] = $skills[$tn]['score'];
        $techAvg = count($techScores) ? (int) round(array_sum($techScores)/count($techScores)) : 70;

        $overallLevelTxt = $overall >= 80 ? 'expert' : ($overall >= 65 ? 'proficient' : ($overall >= 40 ? 'developing' : 'beginner'));

        InterviewReport::create([
            'session_id'              => $session->id,
            'overall_score'           => $overall,
            'technical_score'         => $techAvg,
            'communication_score'     => min(100, $commAvg + rand(-3, 3)),
            'problem_solving_score'   => min(100, $overall + rand(-5, 5)),
            'answer_structure_score'  => min(100, $overall + rand(-5, 5)),
            'summary'                 => "Sesi interview {$position->name} level {$overallLevelTxt} dengan skor {$overall}. Kuat di area: " . implode(', ', $strongest) . ". Perlu ditingkatkan: " . implode(', ', $weakest) . ".",
            'strengths'               => array_merge($strongest, ['Komunikasi lancar', 'Sudah pakai contoh kerja nyata']),
            'weaknesses'              => array_merge($weakest, ['Beberapa jawaban belum kuantitatif', 'Belum konsisten format STAR']),
            'skill_gaps'              => array_merge($weakest, ['Sebutkan metrik kuantitatif', 'Latih storytelling STAR']),
            'recommendations'         => $recommendations,
            'report_version'          => 'REPORT_V1',
            'created_at'              => $session->completed_at,
        ]);

        return $session;
    }

    private function createInterviewing(User $user, Position $position, InterviewMode $mode, int $total, int $answered): InterviewSession
    {
        $startedAt = now()->subMinutes(rand(30, 90));
        $session = InterviewSession::create([
            'user_id'         => $user->id,
            'position_id'     => $position->id,
            'mode'            => $mode,
            'difficulty'      => 'adaptive',
            'question_count'  => $total,
            'status'          => InterviewStatus::Interviewing,
            'started_at'      => $startedAt,
        ]);

        $cats  = ['technical', 'behavioral', 'project_experience'];
        $diffs = ['easy', 'medium', 'hard'];

        for ($i = 1; $i <= $total; $i++) {
            $q = InterviewQuestion::create([
                'session_id'    => $session->id,
                'sequence'      => $i,
                'question_text' => "[{$position->name}] Pertanyaan {$i} demo - sedang berjalan",
                'category'      => $cats[array_rand($cats)],
                'difficulty'    => $diffs[array_rand($diffs)],
                'created_at'    => $startedAt->clone()->addMinutes($i * 4),
            ]);

            if ($i <= $answered) {
                $ans = InterviewAnswer::create([
                    'question_id'       => $q->id,
                    'answer_text'       => 'Jawaban kandidat demo sedang berjalan.',
                    'submitted_at'      => $q->created_at?->clone()->addMinutes(4),
                    'processing_status' => rand(0, 1) ? 'done' : 'processing',
                ]);

                if ($ans->processing_status === 'done') {
                    $sc = rand(50, 80);
                    AnswerEvaluation::create([
                        'answer_id'          => $ans->id,
                        'overall_score'      => $sc,
                        'relevance_score'    => $sc,
                        'knowledge_score'    => $sc,
                        'clarity_score'      => $sc,
                        'completeness_score' => $sc,
                        'reasoning_score'    => $sc,
                        'strengths'          => ['Jawaban terstruktur'],
                        'weaknesses'         => ['Kurang detail'],
                        'missing_points'     => ['Metrik hasil'],
                        'improvement_advice' => 'Perbaiki detail jawaban.',
                        'example_answer'     => 'Contoh: di proyek X saya meningkatkan Y sebesar Z%.',
                        'created_at'         => now(),
                    ]);
                }
            }
        }
        return $session;
    }

    private function createFailed(User $user, Position $position, InterviewMode $mode, int $total, $when): InterviewSession
    {
        $started = $when->clone()->subHours(2);
        $session = InterviewSession::create([
            'user_id'         => $user->id,
            'position_id'     => $position->id,
            'mode'            => $mode,
            'difficulty'      => 'adaptive',
            'question_count'  => $total,
            'status'          => InterviewStatus::Failed,
            'started_at'      => $started,
            'completed_at'    => $when->clone()->addMinutes(15),
        ]);

        for ($i = 1; $i <= min(3, $total); $i++) {
            InterviewQuestion::create([
                'session_id'    => $session->id,
                'sequence'      => $i,
                'question_text' => "Pertanyaan {$i} sesi lama yang diabaikan.",
                'category'      => 'behavioral',
                'difficulty'    => 'medium',
                'created_at'    => $started->clone()->addMinutes($i * 5),
            ]);
        }
        return $session;
    }

    private function createPending(User $user, Position $position, InterviewMode $mode, int $total): InterviewSession
    {
        return InterviewSession::create([
            'user_id'         => $user->id,
            'position_id'     => $position->id,
            'mode'            => $mode,
            'difficulty'      => 'adaptive',
            'question_count'  => $total,
            'status'          => InterviewStatus::Pending,
            'started_at'      => now(),
        ]);
    }
}
