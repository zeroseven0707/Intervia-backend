<?php

namespace App\Services\AI;

use Exception;
use Prism\Prism\Prism;

class AnswerEvaluatorService extends BaseAIService
{
    /**
     * Evaluate a single interview answer.
     *
     * @return array{
     *   overall_score: int,
     *   relevance_score: int,
     *   knowledge_score: int,
     *   clarity_score: int,
     *   completeness_score: int,
     *   reasoning_score: int,
     *   strengths: array,
     *   weaknesses: array,
     *   missing_points: array,
     *   improvement_advice: string,
     *   example_answer: string
     * }
     */
    public function evaluate(
        string $question,
        string $answer,
        string $position,
        string $seniority,
        string $skill
    ): array {
        return $this->withFallback(function ($provider, $providerName) use ($question, $answer, $position, $seniority, $skill) {
            $model  = $this->getModel('evaluator', $providerName);
            $prompt = $this->buildPrompt($question, $answer, $position, $seniority, $skill);

            $result = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($this->systemPrompt())
                ->withPrompt($prompt)
                ->withMaxTokens(config('ai.max_tokens', 2048))
                ->asJson()
                ->generate();

            $data = json_decode($result->text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Answer evaluator returned invalid JSON.');
            }

            $required = [
                'overall_score', 'relevance_score', 'knowledge_score',
                'clarity_score', 'completeness_score', 'reasoning_score',
                'strengths', 'weaknesses', 'missing_points',
                'improvement_advice', 'example_answer',
            ];
            $this->validateStructuredResponse($data, $required);

            // Clamp scores to 0–100
            foreach (['overall_score', 'relevance_score', 'knowledge_score', 'clarity_score', 'completeness_score', 'reasoning_score'] as $key) {
                $data[$key] = max(0, min(100, (int) $data[$key]));
            }

            return $data;
        });
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are a senior technical interviewer and expert evaluator.
Evaluate interview answers fairly and constructively.
Do NOT reward keyword stuffing — evaluate actual understanding.
Always respond with valid JSON only.
PROMPT;
    }

    private function buildPrompt(string $question, string $answer, string $position, string $seniority, string $skill): string
    {
        return <<<PROMPT
Role being interviewed: {$position} ({$seniority})
Primary skill tested: {$skill}

Question: {$question}

Candidate's Answer: {$answer}

Evaluate the answer and return JSON with this exact structure:
{
  "overall_score": 0-100,
  "relevance_score": 0-100,
  "knowledge_score": 0-100,
  "clarity_score": 0-100,
  "completeness_score": 0-100,
  "reasoning_score": 0-100,
  "strengths": ["string"],
  "weaknesses": ["string"],
  "missing_points": ["string"],
  "improvement_advice": "string (actionable, specific)",
  "example_answer": "string (educational example, not a script to memorize)"
}
PROMPT;
    }
}
