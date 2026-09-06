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
            'Fullstack Developer' => [
                'required'  => ['Laravel', 'Vue.js', 'PostgreSQL', 'REST API', 'Git', 'HTML', 'CSS', 'JavaScript'],
                'preferred' => ['Docker', 'Redis', 'TypeScript', 'TDD', 'CI/CD'],
            ],
            'Frontend Developer' => [
                'required'  => ['React', 'JavaScript', 'TypeScript', 'HTML', 'CSS', 'Git'],
                'preferred' => ['Next.js', 'Tailwind CSS', 'Testing Library', 'Webpack', 'Figma'],
            ],
            'Backend Developer' => [
                'required'  => ['Laravel', 'REST API', 'PostgreSQL', 'Git', 'Authentication'],
                'preferred' => ['Redis', 'Queue', 'Docker', 'TDD', 'Microservices'],
            ],
            'Web Developer' => [
                'required'  => ['HTML', 'CSS', 'JavaScript', 'PHP', 'MySQL', 'Git'],
                'preferred' => ['WordPress', 'React', 'SEO', 'Bootstrap'],
            ],
            'Data Analyst' => [
                'required'  => ['SQL', 'Python', 'Excel', 'Data Visualization', 'Statistics'],
                'preferred' => ['Tableau', 'Power BI', 'Pandas', 'Machine Learning', 'R'],
            ],
            'UI/UX Designer' => [
                'required'  => ['Figma', 'User Research', 'Wireframing', 'Prototyping', 'Design Systems'],
                'preferred' => ['Adobe XD', 'Usability Testing', 'HTML', 'CSS', 'Motion Design'],
            ],
            'Sales' => [
                'required'  => ['Communication', 'Negotiation', 'CRM', 'Lead Generation', 'Closing'],
                'preferred' => ['Salesforce', 'Cold Calling', 'Pipeline Management', 'Presentation Skills'],
            ],
            'Marketing' => [
                'required'  => ['Content Marketing', 'SEO', 'Social Media', 'Analytics', 'Copywriting'],
                'preferred' => ['Google Ads', 'Email Marketing', 'A/B Testing', 'HubSpot', 'Branding'],
            ],
        ];

        foreach ($data as $positionName => $skillGroups) {
            $position = Position::firstOrCreate(
                ['slug' => Str::slug($positionName)],
                ['name' => $positionName, 'is_active' => true]
            );

            foreach ($skillGroups as $importance => $skills) {
                foreach ($skills as $skillName) {
                    $skill = Skill::firstOrCreate(
                        ['slug' => Str::slug($skillName)],
                        [
                            'name'      => $skillName,
                            'category'  => $this->guessCategory($skillName),
                            'is_active' => true,
                        ]
                    );

                    // Attach if not already attached
                    if (!$position->skills()->where('skill_id', $skill->id)->exists()) {
                        $position->skills()->attach($skill->id, ['importance' => $importance]);
                    }
                }
            }
        }

        $this->command->info('Positions and skills seeded.');
    }

    private function guessCategory(string $skill): string
    {
        $technical = [
            'Laravel', 'Vue.js', 'React', 'Next.js', 'PostgreSQL', 'MySQL', 'Redis',
            'Docker', 'Git', 'REST API', 'TypeScript', 'JavaScript', 'PHP', 'Python',
            'HTML', 'CSS', 'SQL', 'Node.js', 'Tailwind CSS', 'Bootstrap', 'TDD',
            'CI/CD', 'Queue', 'Authentication', 'Webpack', 'Microservices', 'R',
            'Pandas', 'Machine Learning', 'Tableau', 'Power BI', 'Figma', 'Adobe XD',
        ];
        $soft = [
            'Communication', 'Negotiation', 'Presentation Skills', 'Lead Generation',
            'Closing', 'Cold Calling', 'User Research', 'Usability Testing',
        ];

        if (in_array($skill, $technical)) return 'technical';
        if (in_array($skill, $soft))      return 'soft-skill';
        return 'general';
    }
}
