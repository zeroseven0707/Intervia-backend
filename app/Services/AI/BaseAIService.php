<?php

namespace App\Services\AI;

use Exception;
use Illuminate\Support\Facades\Log;
use Prism\Prism\Prism;
use Prism\Prism\Enums\Provider;

abstract class BaseAIService
{
    protected string $primaryProvider;
    protected string $fallbackProvider;
    protected int $maxRetries;

    public function __construct()
    {
        $this->primaryProvider  = config('ai.primary_provider', 'openai');
        $this->fallbackProvider = config('ai.fallback_provider', 'gemini');
        $this->maxRetries       = config('ai.max_retries', 3);
    }

    /**
     * Run an AI call with automatic fallback to the secondary provider.
     */
    protected function withFallback(callable $call): mixed
    {
        $lastException = null;
        $providers = [$this->primaryProvider, $this->fallbackProvider];

        foreach ($providers as $provider) {
            $attempts = 0;
            while ($attempts < $this->maxRetries) {
                try {
                    return $call($this->resolveProvider($provider), $provider);
                } catch (Exception $e) {
                    $attempts++;
                    $lastException = $e;

                    Log::warning('AI call failed', [
                        'provider' => $provider,
                        'attempt'  => $attempts,
                        'error'    => $e->getMessage(),
                        'service'  => static::class,
                    ]);

                    if ($attempts < $this->maxRetries) {
                        sleep(1);
                    }
                }
            }
        }

        throw new Exception(
            'All AI providers failed. Last error: ' . $lastException?->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * Resolve provider string to Prism Provider enum.
     */
    protected function resolveProvider(string $provider): Provider
    {
        return match ($provider) {
            'gemini' => Provider::Gemini,
            default  => Provider::OpenAI,
        };
    }

    /**
     * Get the model name for a given task and provider.
     */
    protected function getModel(string $task, string $provider): string
    {
        $taskType = config("ai.task_models.{$task}", 'default');
        $modelKey = $taskType === 'evaluation' ? 'evaluation_model' : 'default_model';

        return config("ai.providers.{$provider}.{$modelKey}");
    }

    /**
     * Validate that an array contains required keys.
     */
    protected function validateStructuredResponse(array $response, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                throw new Exception("AI response missing required field: {$key}");
            }
        }
    }

    /**
     * Generate text via Prism (plain text response).
     */
    protected function generateText(Provider $provider, string $model, string $systemPrompt, string $prompt, int $maxTokens = 1024): string
    {
        $response = Prism::text()
            ->using($provider, $model)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->withMaxTokens($maxTokens)
            ->asText();

        return $response->text;
    }

    /**
     * Generate and parse a JSON response via Prism text (ask AI to return JSON, parse manually).
     * Used when structured schema is complex or nested.
     */
    protected function generateJson(Provider $provider, string $model, string $systemPrompt, string $prompt, int $maxTokens = 2048): array
    {
        $text = $this->generateText($provider, $model, $systemPrompt, $prompt, $maxTokens);

        // Strip markdown code fences if present
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $clean = preg_replace('/\s*```$/', '', $clean);

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('AI returned invalid JSON: ' . json_last_error_msg() . ' | Raw: ' . substr($text, 0, 200));
        }

        return $data;
    }
}
