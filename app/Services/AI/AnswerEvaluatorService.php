<?php

namespace App\Services\AI;

class AnswerEvaluatorService extends BaseAIService
{
    /**
     * Evaluate a single interview answer.
     *
     * @return array{overall_score:int, relevance_score:int, knowledge_score:int, clarity_score:int,
     *               completeness_score:int, reasoning_score:int, strengths:array, weaknesses:array,
     *               missing_points:array, improvement_advice:string, example_answer:string}
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
            $maxTokens = (int) config('ai.max_tokens', 2048);

            $data = $this->generateJson($provider, $model, $this->systemPrompt(), $prompt, $maxTokens);

            $required = [
                'overall_score', 'relevance_score', 'knowledge_score', 'clarity_score',
                'completeness_score', 'reasoning_score', 'strengths', 'weaknesses',
                'missing_points', 'improvement_advice', 'example_answer',
            ];
            $this->validateStructuredResponse($data, $required);

            // Clamp all scores 0–100
            foreach (['overall_score', 'relevance_score', 'knowledge_score', 'clarity_score', 'completeness_score', 'reasoning_score'] as $key) {
                $data[$key] = max(0, min(100, (int) $data[$key]));
            }

            // Ensure arrays
            foreach (['strengths', 'weaknesses', 'missing_points'] as $key) {
                if (!is_array($data[$key])) {
                    $data[$key] = [$data[$key]];
                }
            }

            return $data;
        });
    }

    private function systemPrompt(): string
    {
        return 'You are a senior technical interviewer and expert evaluator. Evaluate interview answers fairly and constructively. Do NOT reward keyword stuffing — evaluate actual understanding. Respond with valid JSON only — no markdown, no explanation.';
    }

    private function buildPrompt(string $question, string $answer, string $position, string $seniority, string $skill): string
    {
        return <<<PROMPT
Role being interviewed: {$position} ({$seniority})
Primary skill tested: {$skill}

Question: {$question}

Candidate's Answer: {$answer}

Evaluate and return this exact JSON:
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
  "improvement_advice": "string — specific and actionable",
  "example_answer": "string — educational, not a script to memorize"
}
PROMPT;
    }
}
