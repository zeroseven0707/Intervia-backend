<?php

namespace App\Services\AI;

class CareerCoachService extends BaseAIService
{
    /**
     * Generate a brief personalised coaching message.
     */
    public function coach(array $context): string
    {
        return $this->withFallback(function ($provider, $providerName) use ($context) {
            $model  = $this->getModel('coach', $providerName);
            $prompt = $this->buildPrompt($context);

            return $this->generateText($provider, $model, $this->systemPrompt(), $prompt, 512);
        });
    }

    private function systemPrompt(): string
    {
        return 'You are a supportive career coach. Give a brief, actionable, encouraging message. Under 150 words. Be specific and honest — not generic.';
    }

    private function buildPrompt(array $ctx): string
    {
        $gaps      = collect($ctx['skill_gaps'] ?? [])->where('status', 'weak')->pluck('skill')->implode(', ');
        $strengths = implode(', ', array_slice($ctx['strengths'] ?? [], 0, 3));

        return <<<PROMPT
The candidate completed a {$ctx['position']} interview scoring {$ctx['overall_score']}/100.
Strong areas: {$strengths}
Weak areas: {$gaps}

Write a brief coaching message:
1. One specific thing they did well
2. The single most important area to improve
3. One concrete action they can take this week
PROMPT;
    }
}
