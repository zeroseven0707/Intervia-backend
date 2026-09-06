<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\UserSkillScore;
use App\Services\AI\LearningRecommendationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class LearningController extends Controller
{
    public function __construct(
        private LearningRecommendationService $recommender
    ) {}

    /**
     * GET /api/v1/learning/recommendations
     * Global recommendations based on user's overall weak skills.
     */
    public function recommendations(Request $request): JsonResponse
    {
        $user = $request->user();

        $skillScores = UserSkillScore::with('skill')
            ->where('user_id', $user->id)
            ->where('score', '<', 75)
            ->orderBy('score')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'skill'  => $s->skill->name,
                'score'  => (float) $s->score,
                'status' => $s->score < 60 ? 'weak' : 'needs_work',
            ])
            ->toArray();

        if (empty($skillScores)) {
            return response()->json([
                'data'    => [],
                'message' => 'No weak skills found. Keep practicing!',
            ]);
        }

        $seniority       = $user->experience_level?->value ?? 'mid';
        $recommendations = $this->recommender->recommend($skillScores, $seniority);

        return response()->json(['data' => $recommendations]);
    }

    /**
     * GET /api/v1/learning/sessions/{session}
     * Recommendations based on a specific completed session's skill gaps.
     */
    public function forSession(Request $request, InterviewSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);

        $report = $session->report;

        if (!$report || empty($report->skill_gaps)) {
            return response()->json([
                'data'    => [],
                'message' => 'No skill gap data available for this session.',
            ]);
        }

        $seniority       = $request->user()->experience_level?->value ?? 'mid';
        $recommendations = $this->recommender->recommend($report->skill_gaps, $seniority);

        return response()->json(['data' => $recommendations]);
    }
}
