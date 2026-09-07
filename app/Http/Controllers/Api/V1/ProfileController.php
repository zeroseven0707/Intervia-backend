<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdatePasswordRequest;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use App\Models\InterviewSession;
use App\Models\UserSkillScore;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * GET /api/v1/profile
     * Extended profile with stats.
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load('targetPosition');

        $totalSessions = InterviewSession::where('user_id', $user->id)->count();
        $completedSessions = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->count();

        $avgScore = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->whereNotNull('overall_score')
            ->avg('overall_score');

        $bestScore = InterviewSession::where('user_id', $user->id)
            ->where('status', 'completed')
            ->max('overall_score');

        $skillCount = UserSkillScore::where('user_id', $user->id)->count();

        return response()->json([
            'data' => [
                'user'  => new UserResource($user),
                'stats' => [
                    'total_sessions'     => $totalSessions,
                    'completed_sessions' => $completedSessions,
                    'average_score'      => $avgScore ? round($avgScore) : null,
                    'best_score'         => $bestScore,
                    'skills_tracked'     => $skillCount,
                ],
            ],
        ]);
    }

    /**
     * PUT /api/v1/profile
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $user = $request->user();
        $data = $request->validated();

        $user->update([
            'name'             => $data['name'],
            'experience_level' => $data['experience_level'] ?? null,
        ]);

        return response()->json([
            'data'    => new UserResource($user->fresh()),
            'message' => 'Profile updated.',
        ]);
    }

    /**
     * PUT /api/v1/profile/password
     */
    public function updatePassword(UpdatePasswordRequest $request): JsonResponse
    {
        $request->user()->update([
            'password' => Hash::make($request->new_password),
        ]);

        return response()->json(['message' => 'Password updated.']);
    }
}
