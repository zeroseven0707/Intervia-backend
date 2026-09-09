<?php

namespace App\Services\AI;

use App\Models\LearningMaterial;

class LearningRecommendationService extends BaseAIService
{
    /**
     * Recommend learning materials for identified skill gaps.
     *
     * STRATEGI DIPERBAIKI (Lebih longgar biar DEMO ADA DATA):
     * 1. Coba match perfect: forSkill + approved + difficulty PERSIS seniority
     * 2. Jika kurang → extend difficulty (mid include junior, senior include mid+junior)
     * 3. Jika masih kurang → match via KATEGORY topic (jika skill terkait category)
     * 4. Jika masih kosong → approved + difficulty match (topik popular)
     *
     * @param array  $skillGaps      [['skill' => string, 'score' => float, 'status' => string], ...]
     * @param string $seniority      junior | mid | senior | lead
     * @param int    $maxPerSkill
     *
     * @return array
     */
    public function recommend(array $skillGaps, string $seniority, int $maxPerSkill = 3): array
    {
        $weakSkills = array_values(array_filter(
            $skillGaps,
            fn($gap) => in_array($gap['status'], ['weak', 'needs_work'], true)
        ));

        $recommendations = [];

        // Jika TIDAK ADA weak skill (misal user baru pertama kali), berikan CURATED STARTER
        if (empty($weakSkills)) {
            $starter = $this->getStarterRecommendations($seniority, $maxPerSkill * 3);
            if ($starter->isNotEmpty()) {
                $recommendations[] = [
                    'skill'     => 'Learning Path Pemula',
                    'score'     => null,
                    'status'    => 'starter',
                    'materials' => $starter->toArray(),
                ];
            }
            return $recommendations;
        }

        foreach ($weakSkills as $gap) {
            $skill     = $gap['skill'];
            $difficultyLevels = $this->expandDifficulty($seniority);

            // ── PASS 1: Perfect match (skill + seniority exact) ──────────
            $materials = LearningMaterial::with(['source', 'topic'])
                ->forSkill($skill)
                ->approved()
                ->byDifficultyList($difficultyLevels)
                ->orderByQuality()
                ->limit($maxPerSkill)
                ->get();

            // ── PASS 2: Jika kurang, coba via KATEGORY TOPIC (broad category inference) ──
            if ($materials->count() < $maxPerSkill) {
                $stillNeed = $maxPerSkill - $materials->count();
                $categoryIds = $this->inferCategoryIdsForSkill($skill);
                if (!empty($categoryIds)) {
                    $excludeIds = $materials->modelKeys();
                    $byCategory = LearningMaterial::with(['source', 'topic'])
                        ->whereIn('topic_id', $categoryIds)
                        ->approved()
                        ->byDifficultyList($difficultyLevels)
                        ->when(!empty($excludeIds), fn($q) => $q->whereNotIn('id', $excludeIds))
                        ->orderByQuality()
                        ->limit($stillNeed)
                        ->get();
                    $materials = $materials->merge($byCategory);
                }
            }

            // ── PASS 3: Jika masih kurang, fallback: APPROVED + difficulty match (popular topics) ──
            if ($materials->count() < $maxPerSkill) {
                $stillNeed = $maxPerSkill - $materials->count();
                $excludeIds = $materials->modelKeys();
                $fallback = LearningMaterial::with(['source', 'topic'])
                    ->approved()
                    ->byDifficultyList($difficultyLevels)
                    ->whereNotIn('id', $excludeIds)
                    ->orderByQuality()
                    ->limit($stillNeed)
                    ->get();
                $materials = $materials->merge($fallback);
            }

            if ($materials->isNotEmpty()) {
                $recommendations[] = [
                    'skill'     => $skill,
                    'score'     => $gap['score'],
                    'status'    => $gap['status'],
                    'materials' => array_values($materials->unique('id')->toArray()),
                ];
            }
        }

        // ── FALLBACK GLOBAL: Jika TIDAK ADA recommendations SAMA SEKALI (data seeder tidak lengkap) ──
        if (empty($recommendations)) {
            $fallback = LearningMaterial::with(['source', 'topic'])
                ->approved()
                ->byDifficultyList($this->expandDifficulty($seniority))
                ->orderByQuality()
                ->limit(6)
                ->get();
            if ($fallback->isNotEmpty()) {
                $recommendations[] = [
                    'skill'     => 'Rekomendasi Umum',
                    'score'     => null,
                    'status'    => 'curated',
                    'materials' => $fallback->toArray(),
                ];
            }
        }

        return $recommendations;
    }

    // ── HELPERS ─────────────────────────────────────────────────────────────

    /**
     * Expand difficulty agar TIDAK TERLALU KETAT (demo friendly).
     * - lead   : senior + mid + junior (semua level)
     * - senior : senior + mid + junior
     * - mid    : mid + junior
     * - junior : junior
     */
    private function expandDifficulty(string $seniority): array
    {
        return match (strtolower($seniority)) {
            'lead', 'senior' => ['senior', 'mid', 'junior'],
            'mid'            => ['mid', 'junior'],
            default          => ['junior'],
        };
    }

    /**
     * Infer LearningTopic ID yang relevan untuk skill berdasarkan mapping sederhana + category LIKE
     * (digunakan jika scope forSkill LIKE name/slug tidak match).
     */
    private function inferCategoryIdsForSkill(string $skill): array
    {
        $map = [
            'sql'              => ['data', 'backend'],
            'database'         => ['data', 'backend'],
            'python'           => ['data'],
            'pandas'           => ['data'],
            'statistics'       => ['data'],
            'data-analysis'    => ['data'],
            'data analysis'    => ['data'],
            'data-viz'         => ['data'],
            'data visualization' => ['data'],
            'typescript'       => ['frontend'],
            'javascript'       => ['frontend'],
            'react'            => ['frontend'],
            'vue'              => ['frontend'],
            'css'              => ['frontend'],
            'html'             => ['frontend'],
            'user research'    => ['ux-research', 'design'],
            'ux'               => ['ux-research', 'design'],
            'ui'               => ['design'],
            'figma'            => ['design'],
            'prioritization'   => ['product'],
            'framework priorit' => ['product'],
            'scrum'            => ['product'],
            'project management' => ['product'],
            'pm'               => ['product'],
            'negotiation'      => ['career'],
            'salary'           => ['career'],
            'public speaking'  => ['soft-skill'],
            'communication'    => ['soft-skill'],
            'docker'           => ['devops'],
            'cicd'             => ['devops'],
            'devops'           => ['devops'],
            'writing'          => ['marketing'],
            'seo'              => ['marketing'],
        ];

        $skillSlug = str($skill)->slug()->toString();
        $skillLower = strtolower($skill);

        $matchedCats = [];
        foreach ($map as $key => $cats) {
            $keyLower = strtolower($key);
            if ($skillLower === $keyLower
                || str_contains($skillLower, $keyLower)
                || str_contains($keyLower, $skillSlug)) {
                $matchedCats = [...$matchedCats, ...$cats];
            }
        }

        $matchedCats = array_values(array_unique($matchedCats));

        if (empty($matchedCats)) return [];

        return \App\Models\LearningTopic::whereIn('category', $matchedCats)
            ->pluck('id')
            ->toArray();
    }

    /**
     * Rekomendasi STARTER jika user TIDAK PUNYA weak skill score sama sekali.
     */
    private function getStarterRecommendations(string $seniority, int $limit = 6): \Illuminate\Database\Eloquent\Collection
    {
        $levels = $this->expandDifficulty($seniority);
        return LearningMaterial::approved()
            ->byDifficultyList($levels)
            ->orderByQuality()
            ->with('topic', 'source')
            ->limit($limit)
            ->get();
    }
}
