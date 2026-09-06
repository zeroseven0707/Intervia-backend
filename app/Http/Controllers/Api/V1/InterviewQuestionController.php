<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InterviewStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Interview\SubmitAnswerRequest;
use App\Models\InterviewQuestion;
use App\Models\InterviewSession;
use App\Services\Interview\InterviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewQuestionController extends Controller
{
    public function __construct(private InterviewService $interviewService) {}

    /**
     * GET /api/v1/sessions/{session}/next-question
     */
    public function next(Request $request, InterviewSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        abort_if(
            in_array($session->status, [InterviewStatus::Completed, InterviewStatus::Failed]),
            422,
            'Interview is no longer active.'
        );

        // All questions have been asked (user may not have answered last one yet)
        $questionCount = $session->questions()->count();
        if ($questionCount >= $session->question_count) {
            return response()->json([
                'data'     => null,
                'finished' => true,
                'message'  => 'All questions have been generated.',
            ]);
        }

        $question = $this->interviewService->nextQuestion($session);

        return response()->json([
            'data' => [
                'id'            => $question->id,
                'sequence'      => $question->sequence,
                'question_text' => $question->question_text,
                'category'      => $question->category,
                'difficulty'    => $question->difficulty,
                'skill'         => $question->skill,
                'is_follow_up'  => $question->is_follow_up,
                'total'         => $session->question_count,
            ],
            'finished' => false,
        ]);
    }

    /**
     * POST /api/v1/sessions/{session}/answer
     */
    public function submitAnswer(
        SubmitAnswerRequest $request,
        InterviewSession    $session
    ): JsonResponse {
        abort_if($session->user_id !== $request->user()->id, 403);
        abort_if($session->status === InterviewStatus::Completed, 422, 'Interview already completed.');
        abort_if($session->status === InterviewStatus::Failed,    422, 'Interview session failed.');

        $question = InterviewQuestion::where('id', $request->question_id)
            ->where('session_id', $session->id)
            ->firstOrFail();

        $result = $this->interviewService->submitAnswer(
            $session,
            $question,
            $request->answer_text
        );

        return response()->json([
            'data' => [
                'evaluation' => [
                    'overall_score'      => $result['evaluation']->overall_score,
                    'relevance_score'    => $result['evaluation']->relevance_score,
                    'knowledge_score'    => $result['evaluation']->knowledge_score,
                    'clarity_score'      => $result['evaluation']->clarity_score,
                    'completeness_score' => $result['evaluation']->completeness_score,
                    'reasoning_score'    => $result['evaluation']->reasoning_score,
                    'strengths'          => $result['evaluation']->strengths,
                    'weaknesses'         => $result['evaluation']->weaknesses,
                    'missing_points'     => $result['evaluation']->missing_points,
                    'improvement_advice' => $result['evaluation']->improvement_advice,
                    'example_answer'     => $result['evaluation']->example_answer,
                ],
                'is_last_question' => $result['is_last_question'],
                'session_status'   => $result['session_status'],
            ],
            'message' => 'Answer submitted.',
        ]);
    }
}
