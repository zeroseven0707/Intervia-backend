<?php

namespace App\Services\AI;

class QuestionGeneratorService extends BaseAIService
{
    /**
     * Generate the next adaptive interview question.
     *
     * @return array{question:string, category:string, skill:string, difficulty:string, is_follow_up:bool}
     */
    public function generate(array $context): array
    {
        return $this->withFallback(function ($provider, $providerName) use ($context) {
            $model  = $this->getModel('interviewer', $providerName);
            $prompt = $this->buildPrompt($context);

            $data = $this->generateJson($provider, $model, $this->systemPrompt(), $prompt, 512);

            $this->validateStructuredResponse($data, ['question', 'category', 'skill', 'difficulty', 'is_follow_up']);

            // Normalize is_follow_up to bool
            $data['is_follow_up'] = (bool) $data['is_follow_up'];

            return $data;
        });
    }

    private function systemPrompt(): string
    {
        return 'You are a professional technical interviewer. Generate one focused interview question. Respond with valid JSON only — no markdown, no explanation. Never repeat questions already asked.';
    }

    private function buildPrompt(array $ctx): string
    {
        $previousQA = '';
        foreach (($ctx['previous_questions'] ?? []) as $i => $q) {
            $a = $ctx['previous_answers'][$i] ?? '(no answer)';
            $previousQA .= "Q: {$q}\nA: {$a}\n\n";
        }

        $skills     = implode(', ', array_column($ctx['skills'] ?? [], 'name'));
        $categories = implode(', ', $ctx['categories'] ?? []);

        return <<<PROMPT
Role: {$ctx['position']} ({$ctx['seniority']})
Mode: {$ctx['mode']}
Skills to cover: {$skills}
Categories: {$categories}
Question {$ctx['question_number']} of {$ctx['total_questions']}

Previous Q&A:
{$previousQA}

Generate the next interview question. Return this exact JSON:
{
  "question": "string",
  "category": "technical|behavioral|project_experience|leadership|communication",
  "skill": "string (primary skill being tested)",
  "difficulty": "easy|medium|hard",
  "is_follow_up": true|false
}

If the last answer was weak or vague, generate a follow-up on that skill. Otherwise, cover a new required skill.
PROMPT;
    }
}
