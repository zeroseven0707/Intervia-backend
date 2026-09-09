<?php

namespace App\Services\AI;

class JobAnalyzerService extends BaseAIService
{
    /**
     * Analyze a job description and return structured data.
     *
     * @return array{position:string, seniority:string, responsibilities:array, skills:array, categories:array}
     */
    public function analyze(
        string $jobDescription,
        ?string $position = null,
        ?string $experienceLevel = null
    ): array {
        return $this->withFallback(function ($provider, $providerName) use ($jobDescription, $position, $experienceLevel) {
            $model  = $this->getModel('job_analyzer', $providerName);
            $prompt = $this->buildPrompt($jobDescription, $position, $experienceLevel);

            $default = 'You are an expert HR analyst. Extract structured information from job descriptions. Respond with valid JSON only — no markdown, no explanation.';
            $systemPrompt = $this->resolveSystemPrompt('job_analyzer', $default);

            $data = $this->generateJson($provider, $model, $systemPrompt, $prompt, 1024);

            $this->validateStructuredResponse($data, ['position', 'seniority', 'skills', 'categories']);

            return $data;
        });
    }

    private function buildPrompt(string $jd, ?string $position, ?string $level): string
    {
        $hints = '';
        if ($position) $hints .= "The user believes this is a {$position} role. ";
        if ($level)    $hints .= "Expected experience level: {$level}. ";

        return <<<PROMPT
{$hints}

Analyze the job description below and return this exact JSON structure:
{
  "position": "string",
  "seniority": "junior|mid|senior|lead",
  "responsibilities": ["string"],
  "skills": [{"name": "string", "importance": "required|preferred"}],
  "categories": ["technical|behavioral|project_experience|leadership|communication"]
}

Job Description:
{$jd}
PROMPT;
    }
}
