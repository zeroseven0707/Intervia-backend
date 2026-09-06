<?php

namespace App\Services\AI;

use Exception;

class QuestionGeneratorService extends BaseAIService
{
    /**
     * Generate the next interview question based on session context.
     *
     * @param array $context {
     *   position: string,
     *   seniority: string,
     *   mode: string,
     *   skills: array,
     *   categories: array,
     *   previous_questions: array,
     *   previous_answers: array,
     *   question_number: int,
     *   total_questions: int
     * }
     *
     * @return array{
     *   question: string,
     *   category: string,
     *   skill: string,
     *   difficulty: string,
     *   is_follow_up: bool
     * }
     */
    public function generate(array $context): array
    {
        return $this->withFallback(function ($provider, $providerName) use ($context) {
            $model  = $this->getModel('interviewer', $providerName);
            $prompt = $this->buildPrompt($context);

            $result = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($this->systemPrompt())
                ->withPrompt($prompt)
                ->withMaxTokens(512)
                ->asJson()
                ->generate();

            $data = json_decode($result->text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Question generator returned invalid JSON.');
            }

            $this->validateStructuredResponse($data, ['question', 'category', 'skill', 'difficulty', 'is_follow_up']);

            return $data;
        });
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are a professional technical interviewer. Generate one focused interview question.
Always respond with valid JSON only. Do not include any explanation outside the JSON.
Never repeat questions that have already been asked.
PROMPT;
    }

    private function buildPrompt(array $ctx): string
    {
        $previousQA = '';
        foreach ($ctx['previous_questions'] as $i => $q) {
            $a = $ctx['previous_answers'][$i] ?? '(no answer)';
            $previousQA .= "Q: {$q}\nA: {$a}\n\n";
        }

        $skills     = implode(', ', array_column($ctx['skills'], 'name'));
        $categories = implode(', ', $ctx['categories']);

        return <<<PROMPT
Role: {$ctx['position']} ({$ctx['seniority']})
Mode: {$ctx['mode']}
Skills to cover: {$skills}
Question categories: {$categories}
Question {$ctx['question_number']} of {$ctx['total_questions']}

Previous Q&A:
{$previousQA}

Generate the next interview question. Return JSON with this structure:
{
  "question": "string",
  "category": "technical|behavioral|project_experience|leadership|communication",
  "skill": "string (the primary skill being tested)",
  "difficulty": "easy|medium|hard",
  "is_follow_up": true|false
}

If the previous answer was weak or vague, generate a follow-up. Otherwise, cover a new skill.
PROMPT;
    }
}
