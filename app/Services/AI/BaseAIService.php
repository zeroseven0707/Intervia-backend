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
        $attempts = 0;
        $lastException = null;

        // Try primary first, then fallback
        $providers = [$this->primaryProvider, $this->fallbackProvider];

        foreach ($providers as $provider) {
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
                        sleep(1); // brief pause before retry
                    }
                }
            }

            // Reset attempt counter for next provider
            $attempts = 0;
        }

        throw new Exception(
            'All AI providers failed after retries. Last error: ' . $lastException?->getMessage(),
            0,
            $lastException
        );
    }

    /**
     * Resolve provider string to Prism Provider enum.
     */
    protected function resolveProvider(string $provider): Provider
    {
        return match($provider) {
            'openai' => Provider::OpenAI,
            'gemini' => Provider::Gemini,
            default  => Provider::OpenAI,
        };
    }

    /**
     * Get the appropriate model for a given task and provider.
     */
    protected function getModel(string $task, string $provider): string
    {
        $taskType = config("ai.task_models.{$task}", 'default');
        $modelKey = $taskType === 'evaluation' ? 'evaluation_model' : 'default_model';

        return config("ai.providers.{$provider}.{$modelKey}");
    }

    /**
     * Validate that the AI response contains required fields.
     */
    protected function validateStructuredResponse(array $response, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                throw new Exception("AI response missing required field: {$key}");
            }
        }
    }
}
