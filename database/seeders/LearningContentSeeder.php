<?php

namespace Database\Seeders;

use App\Models\LearningMaterial;
use App\Models\LearningSource;
use App\Models\LearningTopic;
use App\Models\Skill;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class LearningContentSeeder extends Seeder
{
    public function run(): void
    {
        $this->command->info('--- Learning Content Seeder ---');

        $sources = $this->createSources();
        $topics  = $this->createTopics();
        $materialsCount = $this->createMaterials($sources, $topics);

        $this->command->info("✓ Created: " . count($sources) . " sources, " . count($topics) . " topics, {$materialsCount} materials");
    }

    private function createSources(): array
    {
        $sources = [
            [
                'type'       => 'youtube',
                'title'      => 'Laravel 11 Full Course: Build Modern Application',
                'url'        => 'https://www.youtube.com/watch?v=eUN8Xs3hTEY',
                'publisher'  => 'Laracasts',
                'author'     => 'Jeffrey Way',
                'language'   => 'en',
                'external_id'=> 'eUN8Xs3hTEY',
                'status'     => 'approved',
                'metadata'   => ['duration' => '3:45:20', 'views' => 245600],
            ],
            [
                'type'       => 'youtube',
                'title'      => 'Vue.js 3 Composition API Tutorial for Beginners',
                'url'        => 'https://www.youtube.com/watch?v=I_xLMmNeLDY',
                'publisher'  => 'Vue Mastery',
                'author'     => 'Gregg Pollack',
                'language'   => 'en',
                'external_id'=> 'I_xLMmNeLDY',
                'status'     => 'approved',
                'metadata'   => ['duration' => '1:24:13', 'views' => 134200],
            ],
            [
                'type'       => 'youtube',
                'title'      => 'Belajar React untuk Pemula (Full Course Bahasa Indonesia)',
                'url'        => 'https://www.youtube.com/watch?v=k6GOcRkI2S4',
                'publisher'  => 'Kelas Terbuka',
                'author'     => 'Onno W. Purbo',
                'language'   => 'id',
                'external_id'=> 'k6GOcRkI2S4',
                'status'     => 'approved',
                'metadata'   => ['duration' => '5:10:45', 'views' => 89200],
            ],
            [
                'type'       => 'youtube',
                'title'      => 'SQL Tutorial - Full Database Course for Beginners',
                'url'        => 'https://www.youtube.com/watch?v=HXV3zeQKqGY',
                'publisher'  => 'freeCodeCamp.org',
                'author'     => 'Mike Dane',
                'language'   => 'en',
                'external_id'=> 'HXV3zeQKqGY',
                'status'     => 'approved',
                'metadata'   => ['duration' => '4:20:32', 'views' => 5200000],
            ],
            [
                'type'       => 'article',
                'title'      => 'REST API Design Best Practices Handbook',
                'url'        => 'https://stackoverflow.blog/2020/03/02/best-practices-for-rest-api-design/',
                'publisher'  => 'Stack Overflow Blog',
                'author'     => 'John Au-Yeung',
                'language'   => 'en',
                'status'     => 'approved',
            ],
            [
                'type'       => 'article',
                'title'      => '7 Strategi Prioritisasi Product Manager (RICE, ICE, MoSCoW, dll)',
                'url'        => 'https://productschool.com/blog/product-management-2/prioritization-frameworks/',
                'publisher'  => 'Product School',
                'author'     => 'Carlos Gonzalez',
                'language'   => 'en',
                'status'     => 'approved',
            ],
            [
                'type'       => 'article',
                'title'      => 'Panduan Riset Pengguna UX untuk Pemula',
                'url'        => 'https://uxdesign.cc/ux-research-for-beginners',
                'publisher'  => 'UX Collective',
                'author'     => 'Sarah Doody',
                'language'   => 'en',
                'status'     => 'approved',
            ],
            [
                'type'       => 'youtube',
                'title'      => 'Figma UI Design Tutorial: Build Landing Page Modern',
                'url'        => 'https://www.youtube.com/watch?v=FTl0tl9BGdc',
                'publisher'  => 'DesignCourse',
                'author'     => 'Gary Simon',
                'language'   => 'en',
                'external_id'=> 'FTl0tl9BGdc',
                'status'     => 'approved',
                'metadata'   => ['duration' => '52:18', 'views' => 670000],
            ],
            [
                'type'       => 'documentation',
                'title'      => 'Laravel Official Documentation - Eloquent ORM',
                'url'        => 'https://laravel.com/docs/11.x/eloquent',
                'publisher'  => 'Laravel',
                'author'     => 'Taylor Otwell',
                'language'   => 'en',
                'status'     => 'approved',
            ],
            [
                'type'       => 'youtube',
                'title'      => 'Python Pandas Tutorial: Analisis Data untuk Data Analyst',
                'url'        => 'https://www.youtube.com/watch?v=vmEHCJofslg',
                'publisher'  => 'Keith Galli',
                'author'     => 'Keith Galli',
                'language'   => 'en',
                'external_id'=> 'vmEHCJofslg',
                'status'     => 'approved',
                'metadata'   => ['duration' => '58:21', 'views' => 2100000],
            ],
            [
                'type'       => 'article',
                'title'      => 'Cara Menjawab Pertanyaan "Kelemahanmu Apa?" di Interview',
                'url'        => 'https://www.linkedin.com/pulse/cara-menjawab-kelemahan-interview/',
                'publisher'  => 'LinkedIn',
                'author'     => 'HR Expert',
                'language'   => 'id',
                'status'     => 'approved',
            ],
            [
                'type'       => 'youtube',
                'title'      => 'Negosiasi Gaji di Interview: 7 TIPS Penting!',
                'url'        => 'https://www.youtube.com/watch?v=abcDef12345',
                'publisher'  => 'Career Vid',
                'author'     => 'Coach Tono',
                'language'   => 'id',
                'external_id'=> 'abcDef12345',
                'status'     => 'pending',
                'metadata'   => ['duration' => '14:22'],
            ],
        ];

        $created = [];
        foreach ($sources as $src) {
            $s = LearningSource::firstOrCreate(
                ['url' => $src['url']],
                $src
            );
            $created[] = $s;
        }
        return $created;
    }

    private function createTopics(): array
    {
        $topics = [
            // Technical topics
            ['name' => 'Eloquent ORM',                  'category' => 'backend'],
            ['name' => 'Database Design & SQL',         'category' => 'backend'],
            ['name' => 'Authentication & Security',     'category' => 'backend'],
            ['name' => 'Vue Composition API',           'category' => 'frontend'],
            ['name' => 'React Hooks Fundamentals',      'category' => 'frontend'],
            ['name' => 'CSS Layouting (Flex & Grid)',   'category' => 'frontend'],
            ['name' => 'SQL Query Optimization',        'category' => 'data'],
            ['name' => 'Data Visualization',            'category' => 'data'],
            ['name' => 'Pandas & DataFrames',           'category' => 'data'],
            ['name' => 'Docker Fundamentals',           'category' => 'devops'],
            ['name' => 'CI/CD Pipeline',                'category' => 'devops'],

            // Product & Design
            ['name' => 'Framework Prioritisasi',        'category' => 'product'],
            ['name' => 'User Interview Basics',         'category' => 'ux-research'],
            ['name' => 'Wireframing & Prototyping',     'category' => 'design'],
            ['name' => 'Design System & Tokens',        'category' => 'design'],

            // Soft & Career
            ['name' => 'Menjawab Kelemahan di Interview', 'category' => 'career'],
            ['name' => 'Negosiasi Gaji',                'category' => 'career'],
            ['name' => 'Public Speaking Basics',        'category' => 'soft-skill'],
            ['name' => 'Storytelling untuk PM',         'category' => 'product'],
            ['name' => 'SEO Writing Fundamentals',      'category' => 'marketing'],
        ];

        $created = [];
        foreach ($topics as $t) {
            $topic = LearningTopic::firstOrCreate(
                ['slug' => Str::slug($t['name'])],
                [
                    'name'        => $t['name'],
                    'slug'        => Str::slug($t['name']),
                    'category'    => $t['category'],
                    'description' => 'Topik pembelajaran: ' . $t['name'] . '.',
                ]
            );
            $created[] = $topic;
        }
        return $created;
    }

    private function createMaterials(array $sources, array $topics): int
    {
        $findTopic = function (string $slug) use ($topics) {
            $needle = Str::slug($slug);
            foreach ($topics as $t) {
                if ($t->slug === $needle) return $t;
            }
            return null;
        };
        $findSrc = function (string $partialUrl) use ($sources) {
            foreach ($sources as $s) {
                if (str_contains($s->url, $partialUrl)) return $s;
            }
            return null;
        };
        $skillId = fn($name) => Skill::whereSlug(Str::slug($name))->first()?->id;

        $materials = [
            [
                'title'            => 'Eloquent Relationships: HasOne, HasMany, BelongsToMany',
                'summary'          => 'Panduan lengkap memahami relasi ORM Laravel dari dasar sampai pivot table.',
                'topic_slug'       => 'Eloquent ORM',
                'source_partial'   => 'laravel.com/docs/11.x/eloquent',
                'skills'           => ['Laravel', 'REST API', 'PostgreSQL'],
                'difficulty'       => 'mid',
                'duration_minutes' => 35,
            ],
            [
                'title'            => 'Laravel 11 Full Course - Build Application End to End',
                'summary'          => 'Kursus Laravel 3.5 jam dari setup sampai deployment. Cocok untuk mengulang fundamentalmu.',
                'topic_slug'       => 'Eloquent ORM',
                'source_partial'   => 'eUN8Xs3hTEY',
                'skills'           => ['Laravel', 'Vue.js', 'PostgreSQL'],
                'difficulty'       => 'mid',
                'duration_minutes' => 225,
            ],
            [
                'title'            => 'Belajar Vue 3 Composition API dari Nol',
                'summary'          => 'Refactor Options API ke Composition API step by step.',
                'topic_slug'       => 'Vue Composition API',
                'source_partial'   => 'I_xLMmNeLDY',
                'skills'           => ['Vue.js', 'JavaScript', 'TypeScript'],
                'difficulty'       => 'mid',
                'duration_minutes' => 84,
            ],
            [
                'title'            => 'CSS Flexbox + Grid: Layout Masterclass',
                'summary'          => 'Pahami layout modern CSS tanpa framework.',
                'topic_slug'       => 'CSS Layouting (Flex & Grid)',
                'source_partial'   => 'FTl0tl9BGdc',
                'skills'           => ['CSS', 'HTML', 'Tailwind CSS'],
                'difficulty'       => 'junior',
                'duration_minutes' => 52,
            ],
            [
                'title'            => 'Tutorial SQL: Full Database Course 4 Jam',
                'summary'          => 'Belajar SQL dari SELECT sampai JOIN, INDEX, VIEW.',
                'topic_slug'       => 'SQL Query Optimization',
                'source_partial'   => 'HXV3zeQKqGY',
                'skills'           => ['SQL', 'PostgreSQL', 'MySQL'],
                'difficulty'       => 'junior',
                'duration_minutes' => 260,
            ],
            [
                'title'            => 'Best Practice REST API Design',
                'summary'          => 'Panduan desain endpoint API yang idiomatic: status code, pagination, error format, versioning.',
                'topic_slug'       => 'Authentication & Security',
                'source_partial'   => 'stackoverflow.blog',
                'skills'           => ['REST API', 'Laravel', 'Authentication'],
                'difficulty'       => 'senior',
                'duration_minutes' => 25,
            ],
            [
                'title'            => 'Pandas Analisis Data: Tutorial 1 Jam',
                'summary'          => 'Filter, groupby, pivot, merge DataFrames dengan Pandas.',
                'topic_slug'       => 'Pandas & DataFrames',
                'source_partial'   => 'vmEHCJofslg',
                'skills'           => ['Python', 'Pandas', 'Data Visualization'],
                'difficulty'       => 'mid',
                'duration_minutes' => 58,
            ],
            [
                'title'            => 'Framework Prioritisasi: RICE vs ICE vs MoSCoW',
                'summary'          => 'Perbandingan teknik prioritas untuk PM pilih fitur.',
                'topic_slug'       => 'Framework Prioritisasi',
                'source_partial'   => 'productschool.com',
                'skills'           => ['Product Strategy', 'Prioritization', 'OKR'],
                'difficulty'       => 'junior',
                'duration_minutes' => 30,
            ],
            [
                'title'            => 'Dasar Riset Pengguna UX',
                'summary'          => 'Tipe riset kuantitatif vs kualitatif + template pertanyaan interview.',
                'topic_slug'       => 'User Interview Basics',
                'source_partial'   => 'uxdesign.cc',
                'skills'           => ['User Research', 'Usability Testing', 'Communication'],
                'difficulty'       => 'junior',
                'duration_minutes' => 40,
            ],
            [
                'title'            => 'Wireframing & Prototyping di Figma',
                'summary'          => 'Design Thinking → wireframe low-fi → prototype clickable.',
                'topic_slug'       => 'Wireframing & Prototyping',
                'source_partial'   => 'FTl0tl9BGdc',
                'skills'           => ['Figma', 'Prototyping', 'Wireframing'],
                'difficulty'       => 'junior',
                'duration_minutes' => 52,
            ],
            [
                'title'            => 'React Hooks Full: useState, useEffect, useRef, useMemo',
                'summary'          => 'Belajar semua hooks React dasar sampai advanced.',
                'topic_slug'       => 'React Hooks Fundamentals',
                'source_partial'   => 'k6GOcRkI2S4',
                'skills'           => ['React', 'JavaScript', 'TypeScript'],
                'difficulty'       => 'mid',
                'duration_minutes' => 90,
            ],
            [
                'title'            => 'Cara Jawab "Kelemahan Apa?" Tanpa Jatuhkan Diri',
                'summary'          => 'Framework STAR + 3 contoh jawaban aman tapi tidak membosankan.',
                'topic_slug'       => 'Menjawab Kelemahan di Interview',
                'source_partial'   => 'linkedin.com',
                'skills'           => ['Communication', 'Negotiation', 'Closing'],
                'difficulty'       => 'junior',
                'duration_minutes' => 15,
            ],
        ];

        $count = 0;
        foreach ($materials as $m) {
            $topic  = $findTopic($m['topic_slug']);
            $source = $findSrc($m['source_partial']);
            if (!$topic) continue;

            $material = LearningMaterial::firstOrCreate(
                ['title' => $m['title']],
                [
                    'topic_id'          => $topic?->id,
                    'source_id'         => $source?->id,
                    'title'             => $m['title'],
                    'summary'           => $m['summary'],
                    'difficulty'        => $m['difficulty'],
                    'duration_seconds'  => $m['duration_minutes'] * 60,
                    'published_at'      => now()->subDays(rand(10, 365)),
                ]
            );

            // attach skills (pivot material_skill tergantung schema)
            if ($material->wasRecentlyCreated) {
                $skillIds = [];
                foreach ($m['skills'] as $s) {
                    $id = $skillId($s);
                    if ($id) $skillIds[] = $id;
                }
                if (!empty($skillIds) && method_exists($material, 'skills')) {
                    try {
                        $material->skills()->syncWithoutDetaching($skillIds);
                    } catch (\Throwable $e) {
                        // ignore if pivot table doesn't match
                    }
                }
                $count++;
            }
        }
        return $count;
    }
}
