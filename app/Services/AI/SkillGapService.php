<?php

namespace App\Services\AI;

class SkillGapService extends BaseAIService
{
    /**
     * Aggregate per-question scores into skill gap analysis.
     * This is done in application code (no AI needed for basic aggregation).
     *
     * @param array $evaluations [['skill' => string, 'score' => int], ...]
     * @return array [['skill' => string, 'score' => float, 'status' => string], ...]
     */
    public function aggregate(array $evaluations): array
    {
        // Group scores by skill
        $grouped = [];
        foreach ($evaluations as $eval) {
            $skill = $eval['skill'];
            $grouped[$skill][] = $eval['score'];
        }

        $gaps = [];
        foreach ($grouped as $skill => $scores) {
            $avg    = array_sum($scores) / count($scores);
            $status = $this->getStatus((int) $avg);

            $gaps[] = [
                'skill'  => $skill,
                'score'  => round($avg, 1),
                'status' => $status,
                'count'  => count($scores),
            ];
        }

        // Sort by score ascending (weakest first)
        usort($gaps, fn($a, $b) => $a['score'] <=> $b['score']);

        return $gaps;
    }

    private function getStatus(int $score): string
    {
        return match(true) {
            $score >= 80 => 'strong',
            $score >= 70 => 'good',
            $score >= 60 => 'needs_work',
            default      => 'weak',
        };
    }
}
