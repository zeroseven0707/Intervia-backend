<?php

namespace App\Http\Controllers\Api\V1;

use App\Enums\InterviewStatus;
use App\Http\Controllers\Controller;
use App\Models\InterviewSession;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InterviewReportController extends Controller
{
    /**
     * GET /api/v1/sessions/{session}/report
     */
    public function show(Request $request, InterviewSession $session): JsonResponse
    {
        abort_if($session->user_id !== $request->user()->id, 403);
        abort_if($session->status !== InterviewStatus::Completed, 422, 'Report not ready yet.');

        $report = $session->report;
        abort_if(!$report, 404, 'Report not found.');

        // Full per-question answer review
        $answerReview = $session->questions()
            ->with(['answer.evaluation'])
            ->get()
            ->map(fn($q) => [
                'sequence'   => $q->sequence,
                'question'   => $q->question_text,
                'category'   => $q->category,
                'skill'      => $q->skill,
                'answer'     => $q->answer?->answer_text,
                'evaluation' => $q->answer?->evaluation ? [
                    'overall_score'      => $q->answer->evaluation->overall_score,
                    'strengths'          => $q->answer->evaluation->strengths,
                    'weaknesses'         => $q->answer->evaluation->weaknesses,
                    'missing_points'     => $q->answer->evaluation->missing_points,
                    'improvement_advice' => $q->answer->evaluation->improvement_advice,
                    'example_answer'     => $q->answer->evaluation->example_answer,
                ] : null,
            ]);

        return response()->json([
            'data' => [
                'report' => [
                    'id'                     => $report->id,
                    'overall_score'          => $report->overall_score,
                    'technical_score'        => $report->technical_score,
                    'communication_score'    => $report->communication_score,
                    'problem_solving_score'  => $report->problem_solving_score,
                    'answer_structure_score' => $report->answer_structure_score,
                    'summary'                => $report->summary,
                    'strengths'              => $report->strengths,
                    'weaknesses'             => $report->weaknesses,
                    'recommendations'        => $report->recommendations,
                    'created_at'             => $report->created_at?->toISOString(),
                ],
                'skill_gaps'    => $report->skill_gaps,
                'answer_review' => $answerReview,
            ],
        ]);
    }
}
