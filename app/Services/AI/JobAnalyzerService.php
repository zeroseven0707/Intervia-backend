<?php

namespace App\Services\AI;

use Exception;

class JobAnalyzerService extends BaseAIService
{
    /**
     * Analyze a job description and extract structured data.
     *
     * @param string      $jobDescription
     * @param string|null $position
     * @param string|null $experienceLevel
     *
     * @return array{
     *   position: string,
     *   seniority: string,
     *   responsibilities: array,
     *   skills: array,
     *   categories: array
     * }
     */
    public function analyze(
        string $jobDescription,
        ?string $position = null,
        ?string $experienceLevel = null
    ): array {
        return $this->withFallback(function ($provider, $providerName) use ($jobDescription, $position, $experienceLevel) {
            $model  = $this->getModel('job_analyzer', $providerName);
            $prompt = $this->buildPrompt($jobDescription, $position, $experienceLevel);

            $result = Prism::text()
                ->using($provider, $model)
                ->withSystemPrompt($this->systemPrompt())
                ->withPrompt($prompt)
                ->withMaxTokens(1024)
                ->asJson()
                ->generate();

            $data = json_decode($result->text, true);

            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Job analyzer returned invalid JSON.');
            }

            $this->validateStructuredResponse($data, ['position', 'seniority', 'skills', 'categories']);

            return $data;
        });
    }

    private function systemPrompt(): string
    {
        return <<<PROMPT
You are an expert HR analyst. Extract structured information from job descriptions.
Always respond with valid JSON only. Do not include any explanation outside the JSON.
PROMPT;
    }

    private function buildPrompt(string $jd, ?string $position, ?string $level): string
    {
        $hints = '';
        if ($position) {
            $hints .= "The user believes this is a {$position} role. ";
        }
        if ($level) {
            $hints .= "Expected experience level: {$level}. ";
        }

        return <<<PROMPT
{$hints}

Analyze the following job description and return a JSON object with exactly this structure:
{
  "position": "string",
  "seniority": "junior|mid|senior|lead",
  "responsibilities": ["string"],
  "skills": [
    {"name": "string", "importance": "required|preferred"}
  ],
  "categories": ["technical|behavioral|project_experience|leadership|communication"]
}

Job Description:
{$jd}
PROMPT;
    }
}
