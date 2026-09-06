<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InterviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\CreateSessionRequest;
use App\Models\InterviewSession;
use App\Models\Position;
use App\Services\AI\JobAnalyzerService;
use App\Services\Interview\InterviewStateMachine;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewSessionController extends Controller
{
    public function __construct(
        private JobAnalyzerService    $analyzer,
        private InterviewStateMachine $stateMachine,
    ) {}

    /**
     * GET /api/v1/sessions
     */
    public function index(Request $request): JsonResponse
    {
        $sessions = InterviewSession::forUser($request->user()->id)
            ->with('position')
            ->latest()
            ->paginate(10);

        return response()->json([
            'data' => $sessions->map(fn($s) => $this->sessionSummary($s)),
            'meta' => [
                'current_page' => $sessions->currentPage(),
                'last_page'    => $sessions->lastPage(),
                'per_page'     => $sessions->perPage(),
                'total'        => $sessions->total(),
            ],
        ]);
    }

    /**
     * POST /api/v1/sessions
     */
    public function store(CreateSessionRequest $request): JsonResponse
    {
        $data = $request->validated();
        $user = $request->user();

        $session = InterviewSession::create([
            'user_id'         => $user->id,
            'position_id'     => $data['position_id'] ?? null,
            'job_description' => $data['job_description'] ?? null,
            'mode'            => $data['mode'],
            'difficulty'      => 'adaptive',
            'question_count'  => $data['question_count'],
            'status'          => InterviewStatus::Pending,
            'started_at'      => now(),
        ]);

        // Path B — paste job description: analyze with AI
        if (!empty($data['job_description'])) {
            $this->stateMachine->transition($session, InterviewStatus::Analyzing);

            try {
                $positionName = isset($data['position_id'])
                    ? Position::find($data['position_id'])?->name
                    : null;

                $analysis = $this->analyzer->analyze(
                    $data['job_description'],
                    $positionName,
                    $data['experience_level'] ?? null,
                );

                $session->update(['job_analysis' => $analysis]);
            } catch (\Exception $e) {
                $session->update(['status' => InterviewStatus::Failed]);
                return response()->json([
                    'message' => 'Failed to analyze job description. Please try again.',
                ], 422);
            }
        } else {
            // Path A — position library: build synthetic analysis from position skills
            $this->stateMachine->transition($session, InterviewStatus::Analyzing);

            if ($session->position) {
                $skills = $session->position->skills->map(fn($s) => [
                    'name'       => $s->name,
                    'importance' => $s->pivot->importance,
                ])->toArray();

                $session->update([
                    'job_analysis' => [
                        'position'         => $session->position->name,
                        'seniority'        => $data['experience_level'] ?? 'mid',
                        'responsibilities' => [],
                        'skills'           => $skills,
                        'categories'       => ['technical', 'behavioral', 'project_experience'],
                    ],
                ]);
            }
        }

        return response()->json([
            'data'    => $this->sessionDetail($session->fresh()->load('position')),
            'message' => 'Interview session created.',
        ], 201);
    }

    /**
     * GET /api/v1/sessions/{session}
     */
    public function show(Request $request, InterviewSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403, 'Forbidden.');

        return response()->json([
            'data' => $this->sessionDetail($session->load('position')),
        ]);
    }

    /**
     * DELETE /api/v1/sessions/{session}
     */
    public function destroy(Request $request, InterviewSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403, 'Forbidden.');
        $session->delete();

        return response()->json(['message' => 'Session deleted.']);
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    private function sessionSummary(InterviewSession $s): array
    {
        return [
            'id'             => $s->id,
            'position'       => $s->position?->name ?? 'Custom Job',
            'mode'           => $s->mode->value,
            'status'         => $s->status->value,
            'overall_score'  => $s->overall_score,
            'question_count' => $s->question_count,
            'created_at'     => $s->created_at->toISOString(),
            'completed_at'   => $s->completed_at?->toISOString(),
        ];
    }

    private function sessionDetail(InterviewSession $s): array
    {
        return [
            'id'              => $s->id,
            'position'        => $s->position
                ? ['id' => $s->position->id, 'name' => $s->position->name]
                : null,
            'job_description' => $s->job_description,
            'job_analysis'    => $s->job_analysis,
            'mode'            => $s->mode->value,
            'difficulty'      => $s->difficulty,
            'question_count'  => $s->question_count,
            'status'          => $s->status->value,
            'overall_score'   => $s->overall_score,
            'started_at'      => $s->started_at?->toISOString(),
            'completed_at'    => $s->completed_at?->toISOString(),
            'created_at'      => $s->created_at->toISOString(),
        ];
    }
}
