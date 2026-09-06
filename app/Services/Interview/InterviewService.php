<?php

namespace App\Services\Interview;

use App\Enums\InterviewStatus;
use App\Models\InterviewAnswer;
use App\Models\InterviewQuestion;
use App\Models\InterviewReport;
use App\Models\InterviewSession;
use App\Models\UserSkillScore;
use App\Models\UserProgress;
use App\Services\AI\AnswerEvaluatorService;
use App\Services\AI\QuestionGeneratorService;
use App\Services\AI\SkillGapService;
use Exception;
use Illuminate\Support\Facades\DB;

class InterviewService
{
    public function __construct(
        private QuestionGeneratorService $questionGenerator,
        private AnswerEvaluatorService   $evaluator,
        private SkillGapService          $skillGapService,
        private InterviewStateMachine    $stateMachine,
    ) {}

    /**
     * Generate the next question for a session.
     */
    public function nextQuestion(InterviewSession $session): InterviewQuestion
    {
        if ($session->isFinished()) {
            throw new Exception('Interview is already finished.');
        }

        $questions = $session->questions()->with('answer')->get();
        $answeredQuestions = $questions->filter(fn($q) => $q->answer !== null);

        // Build context for AI
        $jobAnalysis = $session->job_analysis ?? [];
        $context = [
            'position'           => $jobAnalysis['position']  ?? $session->position?->name ?? 'General',
            'seniority'          => $jobAnalysis['seniority'] ?? 'mid',
            'mode'               => $session->mode->value,
            'skills'             => $jobAnalysis['skills']    ?? [],
            'categories'         => $jobAnalysis['categories'] ?? ['technical', 'behavioral'],
            'previous_questions' => $questions->pluck('question_text')->toArray(),
            'previous_answers'   => $answeredQuestions->pluck('answer.answer_text')->toArray(),
            'question_number'    => $questions->count() + 1,
            'total_questions'    => $session->question_count,
        ];

        $aiResult = $this->questionGenerator->generate($context);

        // Find the parent if this is a follow-up
        $parentId = null;
        if ($aiResult['is_follow_up'] && $questions->isNotEmpty()) {
            $parentId = $questions->last()->id;
        }

        $question = InterviewQuestion::create([
            'session_id'         => $session->id,
            'sequence'           => $questions->count() + 1,
            'question_text'      => $aiResult['question'],
            'category'           => $aiResult['category'],
            'difficulty'         => $aiResult['difficulty'],
            'skill'              => $aiResult['skill'],
            'parent_question_id' => $parentId,
            'is_follow_up'       => $aiResult['is_follow_up'],
            'created_at'         => now(),
        ]);

        // Transition status if first question
        if ($session->status === InterviewStatus::Analyzing) {
            $this->stateMachine->transition($session, InterviewStatus::Interviewing);
        }

        return $question;
    }

    /**
     * Submit and evaluate an answer.
     */
    public function submitAnswer(
        InterviewSession  $session,
        InterviewQuestion $question,
        string            $answerText
    ): array {
        // Prevent duplicate submission
        if ($question->answer !== null) {
            throw new Exception('This question has already been answered.');
        }

        // Save answer
        $answer = InterviewAnswer::create([
            'question_id'       => $question->id,
            'answer_text'       => $answerText,
            'submitted_at'      => now(),
            'processing_status' => 'processing',
        ]);

        $jobAnalysis = $session->job_analysis ?? [];

        try {
            $eval = $this->evaluator->evaluate(
                question:  $question->question_text,
                answer:    $answerText,
                position:  $jobAnalysis['position']  ?? $session->position?->name ?? 'General',
                seniority: $jobAnalysis['seniority'] ?? 'mid',
                skill:     $question->skill          ?? 'General',
            );

            $answer->update(['processing_status' => 'done']);

            $evaluation = $answer->evaluation()->create([
                'overall_score'      => $eval['overall_score'],
                'relevance_score'    => $eval['relevance_score'],
                'knowledge_score'    => $eval['knowledge_score'],
                'clarity_score'      => $eval['clarity_score'],
                'completeness_score' => $eval['completeness_score'],
                'reasoning_score'    => $eval['reasoning_score'],
                'strengths'          => $eval['strengths'],
                'weaknesses'         => $eval['weaknesses'],
                'missing_points'     => $eval['missing_points'],
                'improvement_advice' => $eval['improvement_advice'],
                'example_answer'     => $eval['example_answer'],
                'created_at'         => now(),
            ]);
        } catch (Exception $e) {
            $answer->update(['processing_status' => 'failed']);
            throw $e;
        }

        // If all questions answered, finalize
        if ($session->fresh()->isFinished()) {
            $this->finalizeSession($session->fresh());
        }

        return [
            'evaluation'       => $evaluation,
            'is_last_question' => $session->fresh()->isFinished(),
            'session_status'   => $session->fresh()->status->value,
        ];
    }

    /**
     * Build the final report after all answers are evaluated.
     */
    public function finalizeSession(InterviewSession $session): void
    {
        $this->stateMachine->transition($session, InterviewStatus::Evaluating);

        $questions = $session->questions()
            ->with(['answer.evaluation'])
            ->get();

        // Collect evaluations
        $evaluations = $questions
            ->map(fn($q) => $q->answer?->evaluation)
            ->filter();

        if ($evaluations->isEmpty()) {
            $this->stateMachine->transition($session, InterviewStatus::Failed);
            return;
        }

        // Overall score = average of all overall_scores
        $overallScore = (int) round($evaluations->avg('overall_score'));

        // Category scores
        $technicalEvals = $questions->filter(fn($q) => $q->category === 'technical')
            ->map(fn($q) => $q->answer?->evaluation)->filter();
        $technicalScore = $technicalEvals->isNotEmpty()
            ? (int) round($technicalEvals->avg('overall_score')) : null;

        // Aggregate skill gaps
        $skillData = $questions->map(fn($q) => [
            'skill' => $q->skill ?? 'General',
            'score' => $q->answer?->evaluation?->overall_score ?? 0,
        ])->toArray();

        $skillGaps = $this->skillGapService->aggregate($skillData);

        // Aggregate strengths/weaknesses from evaluations
        $allStrengths  = $evaluations->flatMap(fn($e) => $e->strengths  ?? [])->unique()->values()->toArray();
        $allWeaknesses = $evaluations->flatMap(fn($e) => $e->weaknesses ?? [])->unique()->values()->toArray();

        // Save report
        $report = InterviewReport::create([
            'session_id'      => $session->id,
            'overall_score'   => $overallScore,
            'technical_score' => $technicalScore,
            'summary'         => "Completed {$questions->count()} questions with an overall score of {$overallScore}/100.",
            'strengths'       => array_slice($allStrengths, 0, 5),
            'weaknesses'      => array_slice($allWeaknesses, 0, 5),
            'skill_gaps'      => $skillGaps,
            'recommendations' => [],
            'created_at'      => now(),
        ]);

        // Update session overall score
        $session->update([
            'overall_score' => $overallScore,
            'completed_at'  => now(),
        ]);

        // Update user skill scores
        $this->updateUserSkillScores($session, $skillGaps);

        $this->stateMachine->transition($session->fresh(), InterviewStatus::Completed);
    }

    private function updateUserSkillScores(InterviewSession $session, array $skillGaps): void
    {
        foreach ($skillGaps as $gap) {
            // Try to find matching skill by name
            $skill = \App\Models\Skill::where('name', 'like', "%{$gap['skill']}%")->first();
            if (!$skill) continue;

            $existing = UserSkillScore::where('user_id', $session->user_id)
                ->where('skill_id', $skill->id)
                ->first();

            if ($existing) {
                // Track progress
                UserProgress::create([
                    'user_id'          => $session->user_id,
                    'skill_id'         => $skill->id,
                    'previous_score'   => $existing->score,
                    'current_score'    => $gap['score'],
                    'improvement'      => $gap['score'] - $existing->score,
                    'last_assessed_at' => now(),
                ]);

                $existing->update([
                    'score'             => $gap['score'],
                    'source_session_id' => $session->id,
                    'updated_at'        => now(),
                ]);
            } else {
                UserSkillScore::create([
                    'user_id'           => $session->user_id,
                    'skill_id'          => $skill->id,
                    'score'             => $gap['score'],
                    'source_session_id' => $session->id,
                    'updated_at'        => now(),
                ]);
            }
        }
    }
}
