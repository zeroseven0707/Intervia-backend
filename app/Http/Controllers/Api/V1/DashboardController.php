<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use App\Models\UserSkillScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * GET /api/v1/dashboard
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Two latest completed sessions for score + trend
        $completedSessions = InterviewSession::forUser($user->id)
            ->completed()
            ->latest()
            ->limit(2)
            ->get();

        $latestSession   = $completedSessions->get(0);
        $previousSession = $completedSessions->get(1);

        $latestScore   = $latestSession?->overall_score;
        $previousScore = $previousSession?->overall_score;
        $trend         = ($latestScore !== null && $previousScore !== null)
            ? $latestScore - $previousScore
            : null;

        // Weak skills (score < 70)
        $weakSkills = UserSkillScore::with('skill')
            ->where('user_id', $user->id)
            ->where('score', '<', 70)
            ->orderBy('score')
            ->limit(5)
            ->get()
            ->map(fn($s) => [
                'skill'  => $s->skill->name,
                'score'  => $s->score,
                'status' => $s->score < 60 ? 'weak' : 'needs_work',
            ]);

        // Readiness score = average of all skill scores
        $allScores      = UserSkillScore::where('user_id', $user->id)->pluck('score');
        $readinessScore = $allScores->isNotEmpty()
            ? (int) round($allScores->average())
            : null;

        // Recent sessions
        $recentSessions = InterviewSession::forUser($user->id)
            ->with('position')
            ->latest()
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'readiness_score' => $readinessScore,
                'latest_score'    => $latestScore,
                'previous_score'  => $previousScore,
                'score_trend'     => $trend,
                'weak_skills'     => $weakSkills,
                'recent_sessions' => $recentSessions->map(fn($s) => [
                    'id'            => $s->id,
                    'position'      => $s->position?->name ?? 'Custom Job',
                    'mode'          => $s->mode->value,
                    'status'        => $s->status->value,
                    'overall_score' => $s->overall_score,
                    'created_at'    => $s->created_at->toISOString(),
                ]),
            ],
        ]);
    }
}
