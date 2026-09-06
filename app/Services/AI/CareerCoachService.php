<?php

namespace App\Services\AI;

use Prism\Prism\Prism;
use Exception;

class CareerCoachService extends BaseAIService
{
    /**
     * Generate a personalised coaching message based on session results.
     *
     * @param array $context {
     *   position: string,
     *   overall_score: int,
     *   skill_gaps: array,
     *   strengths: array,
     *   weaknesses: array
     * }
     */
    public function coach(array $context): string
    {
        return $this->withFallback(function ($provider, $providerName) use ($context) {
            $model  = $this->getModel('coach', $providerName);
            $prompt = $this->buildPrompt($context);

            $result = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($this->systemPrompt())
                ->withPrompt($prompt)
                ->withMaxTokens(512)
                ->generate();

            return trim($result->text);
        });
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are a supportive career coach. Give a brief, actionable, encouraging coaching message.
Keep it under 150 words. Be specific and honest — not generic.
PROMPT;
    }

    private function buildPrompt(array $ctx): string
    {
        $gaps = collect($ctx['skill_gaps'] ?? [])
            ->where('status', 'weak')
            ->pluck('skill')
            ->implode(', ');

        $strengths = implode(', ', array_slice($ctx['strengths'] ?? [], 0, 3));

        return <<<PROMPT
The candidate just completed a {$ctx['position']} interview with a score of {$ctx['overall_score']}/100.

Strong areas: {$strengths}
Weak areas: {$gaps}

Write a brief coaching message focusing on:
1. One specific thing they did well
2. The single most important area to improve
3. One concrete action they can take this week
PROMPT;
    }
}
