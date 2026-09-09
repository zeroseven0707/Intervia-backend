<?php

namespace Database\Seeders;

use App\Models\Position;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class PositionSkillSeeder extends Seeder
{
    public function run(): void
    {
        $data = [
            // ── Engineering ────────────────────────────────────────────────
            'Fullstack Developer' => [
                'description' => 'Membangun aplikasi web end-to-end dari database sampai UI.',
                'required'  => ['Laravel', 'Vue.js', 'PostgreSQL', 'REST API', 'Git', 'HTML', 'CSS', 'JavaScript'],
                'preferred' => ['Docker', 'Redis', 'TypeScript', 'TDD', 'CI/CD', 'Tailwind CSS', 'Next.js'],
            ],
            'Frontend Developer' => [
                'description' => 'Membangun antarmuka pengguna yang cepat, aksesibel, dan menarik.',
                'required'  => ['React', 'JavaScript', 'TypeScript', 'HTML', 'CSS', 'Git'],
                'preferred' => ['Next.js', 'Tailwind CSS', 'Testing Library', 'Webpack', 'Figma', 'Vite'],
            ],
            'Backend Developer' => [
                'description' => 'Membangun API dan sistem backend yang scalable dan aman.',
                'required'  => ['Laravel', 'REST API', 'PostgreSQL', 'Git', 'Authentication'],
                'preferred' => ['Redis', 'Queue', 'Docker', 'TDD', 'Microservices', 'AWS', 'GraphQL'],
            ],
            'Web Developer' => [
                'description' => 'Membangun website dan aplikasi web berbasis PHP/JS modern.',
                'required'  => ['HTML', 'CSS', 'JavaScript', 'PHP', 'MySQL', 'Git'],
                'preferred' => ['WordPress', 'React', 'SEO', 'Bootstrap', 'jQuery'],
            ],
            'Mobile Developer Android' => [
                'description' => 'Membangun aplikasi Android berkualitas tinggi dengan Kotlin.',
                'required'  => ['Kotlin', 'Android SDK', 'REST API', 'Git', 'Material Design'],
                'preferred' => ['Jetpack Compose', 'Firebase', 'Room Database', 'Dagger Hilt', 'Coroutines'],
            ],
            'iOS Developer' => [
                'description' => 'Membangun aplikasi iOS dengan Swift dan SwiftUI.',
                'required'  => ['Swift', 'iOS SDK', 'Xcode', 'Git', 'Auto Layout'],
                'preferred' => ['SwiftUI', 'Combine', 'CoreData', 'Firebase', 'Objective-C'],
            ],
            'DevOps Engineer' => [
                'description' => 'Mengelola infrastruktur, CI/CD, dan otomatisasi deployment.',
                'required'  => ['Linux', 'Docker', 'CI/CD', 'Shell Scripting', 'Git'],
                'preferred' => ['Kubernetes', 'AWS', 'Terraform', 'Nginx', 'Ansible', 'Monitoring'],
            ],
            'Data Engineer' => [
                'description' => 'Membangun pipeline dan gudang data untuk analisis skala besar.',
                'required'  => ['Python', 'SQL', 'ETL', 'Data Warehouse', 'Git'],
                'preferred' => ['Airflow', 'Spark', 'BigQuery', 'AWS', 'Kafka', 'dbt'],
            ],
            'Quality Assurance (QA)' => [
                'description' => 'Memastikan kualitas produk melalui pengujian manual & otomatis.',
                'required'  => ['Manual Testing', 'Test Case Writing', 'Bug Tracking', 'API Testing', 'Git'],
                'preferred' => ['Selenium', 'Cypress', 'JMeter', 'Postman', 'CI/CD'],
            ],

            // ── Data & Analytics ────────────────────────────────────────────
            'Data Analyst' => [
                'description' => 'Menganalisis data dan menghasilkan insight untuk keputusan bisnis.',
                'required'  => ['SQL', 'Python', 'Excel', 'Data Visualization', 'Statistics'],
                'preferred' => ['Tableau', 'Power BI', 'Pandas', 'Machine Learning', 'R'],
            ],
            'Data Scientist' => [
                'description' => 'Membangun model prediktif dan sistem ML untuk memecahkan masalah bisnis.',
                'required'  => ['Python', 'SQL', 'Machine Learning', 'Statistics', 'Data Cleaning'],
                'preferred' => ['TensorFlow', 'PyTorch', 'Scikit-learn', 'MLOps', 'Experiment Tracking'],
            ],
            'Business Intelligence (BI) Analyst' => [
                'description' => 'Membangun dashboard dan laporan untuk memantau metrik bisnis.',
                'required'  => ['SQL', 'Tableau', 'Power BI', 'Data Modeling', 'Business Acumen'],
                'preferred' => ['Looker', 'ETL', 'Python', 'BigQuery', 'Snowflake'],
            ],

            // ── Product & Design ───────────────────────────────────────────
            'Product Manager' => [
                'description' => 'Mendefinisikan roadmap produk dan mengirim fitur yang memberikan dampak.',
                'required'  => ['Product Strategy', 'User Research', 'Prioritization', 'Data Analysis', 'Stakeholder Management'],
                'preferred' => ['SQL', 'Figma', 'A/B Testing', 'OKR', 'Jira', 'Agile'],
            ],
            'Associate Product Manager (APM)' => [
                'description' => 'Rol entry-level PM, fokus pada fitur spesifik dan eksekusi.',
                'required'  => ['Problem Solving', 'User Research', 'Prioritization', 'Communication'],
                'preferred' => ['SQL', 'Figma', 'Data Analysis', 'OKR', 'Jira'],
            ],
            'UI/UX Designer' => [
                'description' => 'Merancang antarmuka dan pengalaman pengguna yang intuitif.',
                'required'  => ['Figma', 'User Research', 'Wireframing', 'Prototyping', 'Design Systems'],
                'preferred' => ['Adobe XD', 'Usability Testing', 'HTML', 'CSS', 'Motion Design'],
            ],
            'UX Researcher' => [
                'description' => 'Menganalisis perilaku pengguna melalui riset kualitatif dan kuantitatif.',
                'required'  => ['User Interview', 'Survey Design', 'Data Analysis', 'Synthesis', 'Usability Testing'],
                'preferred' => ['SQL', 'Mixpanel', 'Hotjar', 'Figma', 'Statistics'],
            ],
            'Graphic Designer' => [
                'description' => 'Membuat desain visual untuk media sosial, cetak, dan branding.',
                'required'  => ['Adobe Photoshop', 'Adobe Illustrator', 'Typography', 'Color Theory', 'Branding'],
                'preferred' => ['Figma', 'After Effects', 'Premiere Pro', 'Motion Design'],
            ],

            // ── Marketing & Sales ──────────────────────────────────────────
            'Digital Marketing' => [
                'description' => 'Menjalankan strategi marketing digital akuisisi & retensi.',
                'required'  => ['Content Marketing', 'SEO', 'Social Media', 'Analytics', 'Copywriting'],
                'preferred' => ['Google Ads', 'Email Marketing', 'A/B Testing', 'HubSpot', 'Branding'],
            ],
            'Content Writer' => [
                'description' => 'Membuat konten berkualitas untuk blog, sosial media, dan materi lainnya.',
                'required'  => ['Copywriting', 'SEO Writing', 'Research', 'Storytelling', 'Grammar'],
                'preferred' => ['CMS', 'Keyword Research', 'Email Copy', 'Video Script'],
            ],
            'Social Media Specialist' => [
                'description' => 'Mengelola akun sosial media dan strategi konten.',
                'required'  => ['Social Media Strategy', 'Content Planning', 'Community Management', 'Analytics', 'Copywriting'],
                'preferred' => ['TikTok Ads', 'Meta Ads', 'Canva', 'Influencer Marketing'],
            ],
            'Sales Executive' => [
                'description' => 'Mencari lead, demo produk, dan close deal penjualan.',
                'required'  => ['Communication', 'Negotiation', 'CRM', 'Lead Generation', 'Closing'],
                'preferred' => ['Salesforce', 'Cold Calling', 'Pipeline Management', 'Presentation Skills'],
            ],
            'Account Manager' => [
                'description' => 'Merawat hubungan klien dan memastikan kepuasan serta perpanjangan kontrak.',
                'required'  => ['Client Relationship', 'Communication', 'Problem Solving', 'Project Management'],
                'preferred' => ['CRM', 'Upselling', 'NPS', 'Renewal Strategy'],
            ],

            // ── Business & Operations ──────────────────────────────────────
            'Human Resources (HR) Generalist' => [
                'description' => 'Mengelola recruitment, onboarding, karyawan, dan budaya.',
                'required'  => ['Recruitment', 'Employee Relations', 'Onboarding', 'HR Administration'],
                'preferred' => ['Talent Development', 'HRIS', 'Payroll', 'Performance Management'],
            ],
            'Recruitment Specialist' => [
                'description' => 'Mencari dan merekrut talenta terbaik untuk perusahaan.',
                'required'  => ['Sourcing', 'Interviewing', 'ATS', 'Stakeholder Management'],
                'preferred' => ['LinkedIn Recruiter', 'Employer Branding', 'Assessment'],
            ],
            'Finance Analyst' => [
                'description' => 'Menganalisis performa keuangan dan menyusun anggaran.',
                'required'  => ['Financial Analysis', 'Excel', 'Budgeting', 'Accounting'],
                'preferred' => ['SQL', 'Power BI', 'Python', 'FP&A', 'Valuation'],
            ],
            'Operations Manager' => [
                'description' => 'Mengelola operasional harian dan meningkatkan efisiensi.',
                'required'  => ['Process Improvement', 'Project Management', 'Data Analysis', 'Leadership'],
                'preferred' => ['SQL', 'SOP', 'OKR', 'Supply Chain'],
            ],
            'Customer Support' => [
                'description' => 'Memberikan pelayanan prima kepada pelanggan melalui berbagai channel.',
                'required'  => ['Communication', 'Problem Solving', 'CRM', 'Empathy', 'Ticketing System'],
                'preferred' => ['Live Chat', 'Social Media', 'QA Process', 'Zendesk'],
            ],

            // ── Project Management ──────────────────────────────────────────
            'Project Manager' => [
                'description' => 'Mengelola proyek dari perencanaan sampai deliver selesai tepat waktu.',
                'required'  => ['Project Planning', 'Risk Management', 'Stakeholder Management', 'Agile'],
                'preferred' => ['Jira', 'Asana', 'PMP', 'Scrum Master', 'Budgeting'],
            ],
            'Scrum Master' => [
                'description' => 'Memfasilitasi tim Scrum dan memastikan proses Agile berjalan lancar.',
                'required'  => ['Scrum', 'Facilitation', 'Coaching', 'Agile Values', 'Retrospective'],
                'preferred' => ['Kanban', 'Jira', 'CSM', 'SAFe', 'Conflict Management'],
            ],
        ];

        $positionCount = 0;
        $skillCount = 0;

        foreach ($data as $positionName => $payload) {
            $position = Position::firstOrCreate(
                ['slug' => Str::slug($positionName)],
                [
                    'name'        => $positionName,
                    'description' => $payload['description'] ?? null,
                    'is_active'   => true,
                ]
            );
            if ($position->wasRecentlyCreated) $positionCount++;

            foreach (['required', 'preferred'] as $importance) {
                $skills = $payload[$importance] ?? [];
                foreach ($skills as $skillName) {
                    $skill = Skill::firstOrCreate(
                        ['slug' => Str::slug($skillName)],
                        [
                            'name'      => $skillName,
                            'category'  => $this->guessCategory($skillName),
                            'is_active' => true,
                        ]
                    );
                    if ($skill->wasRecentlyCreated) $skillCount++;

                    if (!$position->skills()->where('skill_id', $skill->id)->exists()) {
                        $position->skills()->attach($skill->id, ['importance' => $importance]);
                    }
                }
            }
        }

        $this->command->info("✓ Positions + Skills seeded: {$positionCount} new positions, {$skillCount} new skills.");
        $this->command->info("  Total: " . Position::count() . " positions, " . Skill::count() . " skills");
    }

    private function guessCategory(string $skill): string
    {
        $technical = [
            'Laravel', 'Vue.js', 'React', 'Next.js', 'PostgreSQL', 'MySQL', 'Redis',
            'Docker', 'Git', 'REST API', 'TypeScript', 'JavaScript', 'PHP', 'Python',
            'HTML', 'CSS', 'SQL', 'Node.js', 'Tailwind CSS', 'Bootstrap', 'TDD',
            'CI/CD', 'Queue', 'Authentication', 'Webpack', 'Microservices', 'R',
            'Pandas', 'Machine Learning', 'Tableau', 'Power BI', 'Figma', 'Adobe XD',
            'Kotlin', 'Android SDK', 'Swift', 'iOS SDK', 'Xcode', 'Jetpack Compose',
            'Firebase', 'SwiftUI', 'Kubernetes', 'AWS', 'Terraform', 'Nginx', 'Ansible',
            'Linux', 'Airflow', 'Spark', 'BigQuery', 'Kafka', 'dbt', 'ETL',
            'Shell Scripting', 'Selenium', 'Cypress', 'JMeter', 'Postman',
            'Scikit-learn', 'TensorFlow', 'PyTorch', 'GraphQL', 'MLOps',
            'Looker', 'Snowflake', 'Vite', 'JQuery', 'Adobe Photoshop',
            'Adobe Illustrator', 'After Effects', 'Premiere Pro', 'CoreData',
            'Combine', 'Objective-C', 'Dagger Hilt', 'Room Database', 'Coroutines',
            'Material Design', 'Auto Layout', 'CMS', 'HRIS', 'Zendesk',
        ];
        $soft = [
            'Communication', 'Negotiation', 'Presentation Skills', 'Lead Generation',
            'Closing', 'Cold Calling', 'User Research', 'Usability Testing',
            'Product Strategy', 'Prioritization', 'Stakeholder Management',
            'A/B Testing', 'OKR', 'Agile', 'Leadership', 'Data Analysis',
            'Problem Solving', 'Copywriting', 'Storytelling', 'Typography',
            'Color Theory', 'Branding', 'Synthesis', 'Data Visualization',
            'Statistics', 'Problem Framing', 'Accounting', 'Financial Analysis',
            'Budgeting', 'Facilitation', 'Coaching', 'Risk Management',
            'Project Planning', 'Process Improvement', 'Project Management',
            'Employee Relations', 'Recruitment', 'Onboarding',
            'Client Relationship', 'Upselling', 'Community Management',
            'Content Planning', 'Employer Branding', 'Assessment',
            'Empathy', 'Conflict Management', 'Retrospective', 'Scrum',
            'User Interview', 'Survey Design', 'Talent Development',
            'Performance Management', 'Renewal Strategy', 'Pipeline Management',
            'Sourcing', 'Interviewing', 'Valuation', 'FP&A', 'SOP',
            'Business Acumen', 'Data Modeling', 'Data Cleaning',
            'Experiment Tracking', 'Manual Testing', 'Test Case Writing',
            'Bug Tracking', 'API Testing', 'Wireframing', 'Prototyping',
            'Design Systems', 'Motion Design', 'Product Roadmap', 'SEO Writing',
            'Research', 'Grammar', 'Keyword Research', 'Email Copy',
            'Video Script', 'Renewal Strategy', 'QA Process', 'Influencer Marketing',
        ];
        $tools = [
            'Jira', 'Asana', 'Figma', 'Slack', 'Notion', 'Canva',
            'Salesforce', 'HubSpot', 'Mixpanel', 'Hotjar', 'Tableau',
            'Power BI', 'PMP', 'CSM', 'SAFe', 'Pandas', 'Numpy',
        ];

        if (in_array($skill, $technical)) return 'technical';
        if (in_array($skill, $soft))      return 'soft-skill';
        if (in_array($skill, $tools))     return 'tools';
        return 'general';
    }
}
