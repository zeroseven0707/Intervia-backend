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
    protected int    $maxRetries;
    private Prism    $prism;

    public function __construct()
    {
        $this->primaryProvider  = config('ai.primary_provider', 'openai');
        $this->fallbackProvider = config('ai.fallback_provider', 'gemini');
        $this->maxRetries       = config('ai.max_retries', 3);
        $this->prism            = app(Prism::class);
    }

    /**
     * Run an AI call with automatic provider fallback.
     */
    protected function withFallback(callable $call): mixed
    {
        $lastException = null;
        $providers     = [$this->primaryProvider, $this->fallbackProvider];

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

    protected function resolveProvider(string $provider): Provider
    {
        return match ($provider) {
            'gemini' => Provider::Gemini,
            default  => Provider::OpenAI,
        };
    }

    protected function getModel(string $task, string $provider): string
    {
        $taskType = config("ai.task_models.{$task}", 'default');
        $modelKey = $taskType === 'evaluation' ? 'evaluation_model' : 'default_model';
        return config("ai.providers.{$provider}.{$modelKey}");
    }

    protected function validateStructuredResponse(array $response, array $requiredKeys): void
    {
        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $response)) {
                throw new Exception("AI response missing required field: {$key}");
            }
        }
    }

    /**
     * Generate plain text via Prism.
     */
    protected function generateText(
        Provider $provider,
        string   $model,
        string   $systemPrompt,
        string   $prompt,
        int      $maxTokens = 1024
    ): string {
        $response = $this->prism
            ->text()
            ->using($provider, $model)
            ->withSystemPrompt($systemPrompt)
            ->withPrompt($prompt)
            ->withMaxTokens($maxTokens)
            ->asText();

        return $response->text;
    }

    /**
     * Generate text and parse as JSON.
     * Strips markdown fences if the model wraps output in ```json blocks.
     */
    protected function generateJson(
        Provider $provider,
        string   $model,
        string   $systemPrompt,
        string   $prompt,
        int      $maxTokens = 2048
    ): array {
        $text  = $this->generateText($provider, $model, $systemPrompt, $prompt, $maxTokens);
        $clean = preg_replace('/^```(?:json)?\s*/i', '', trim($text));
        $clean = preg_replace('/\s*```$/s', '', $clean);

        $data = json_decode($clean, true);

        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception(
                'AI returned invalid JSON: ' . json_last_error_msg() .
                ' | Raw (first 300): ' . substr($text, 0, 300)
            );
        }

        return $data;
    }
}
