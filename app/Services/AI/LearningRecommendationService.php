<?php

namespace App\Services\AI;

use App\Models\LearningMaterial;

class LearningRecommendationService extends BaseAIService
{
    /**
     * Recommend learning materials for identified skill gaps.
     *
     * Priority:
     * 1. Direct skill match
     * 2. Relevance to interview weakness
     * 3. Source quality
     * 4. Difficulty fit
     * 5. Learning time
     *
     * @param array  $skillGaps      [['skill' => string, 'score' => float, 'status' => string], ...]
     * @param string $seniority
     * @param int    $maxPerSkill
     *
     * @return array
     */
    public function recommend(array $skillGaps, string $seniority, int $maxPerSkill = 3): array
    {
        $weakSkills = array_filter(
            $skillGaps,
            fn($gap) => in_array($gap['status'], ['weak', 'needs_work'])
        );

        $recommendations = [];

        foreach ($weakSkills as $gap) {
            $materials = LearningMaterial::forSkill($gap['skill'])
                ->approved()
                ->byDifficulty($seniority)
                ->orderByQuality()
                ->limit($maxPerSkill)
                ->get();

            if ($materials->isNotEmpty()) {
                $recommendations[] = [
                    'skill'     => $gap['skill'],
                    'score'     => $gap['score'],
                    'status'    => $gap['status'],
                    'materials' => $materials->toArray(),
                ];
            }
        }

        return $recommendations;
    }
}
