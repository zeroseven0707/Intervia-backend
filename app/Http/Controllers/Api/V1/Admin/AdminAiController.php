<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\AiPromptTemplate;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class AdminAiController extends Controller
{
    // ── Provider Config ──────────────────────────────────────────────────────

    /**
     * Return current AI configuration (never expose raw API keys — masked).
     */
    public function getConfig(): JsonResponse
    {
        return response()->json([
            'data' => [
                'primary_provider'  => config('ai.primary_provider'),
                'fallback_provider' => config('ai.fallback_provider'),
                'max_retries'       => config('ai.max_retries'),
                'max_tokens'        => config('ai.max_tokens'),
                'timeout_seconds'   => config('ai.timeout_seconds'),
                'providers' => [
                    'openai' => [
                        'has_key'          => !empty(config('ai.providers.openai.api_key')),
                        'default_model'    => config('ai.providers.openai.default_model'),
                        'evaluation_model' => config('ai.providers.openai.evaluation_model'),
                    ],
                    'gemini' => [
                        'has_key'          => !empty(config('ai.providers.gemini.api_key')),
                        'default_model'    => config('ai.providers.gemini.default_model'),
                        'evaluation_model' => config('ai.providers.gemini.evaluation_model'),
                    ],
                ],
                'task_models' => config('ai.task_models'),
            ],
        ]);
    }

    /**
     * Update .env AI keys and model names.
     */
    public function updateConfig(Request $request): JsonResponse
    {
        $data = $request->validate([
            'primary_provider'         => 'sometimes|in:openai,gemini',
            'fallback_provider'        => 'sometimes|in:openai,gemini',
            'openai_api_key'           => 'sometimes|nullable|string',
            'openai_default_model'     => 'sometimes|string|max:100',
            'openai_evaluation_model'  => 'sometimes|string|max:100',
            'gemini_api_key'           => 'sometimes|nullable|string',
            'gemini_default_model'     => 'sometimes|string|max:100',
            'gemini_evaluation_model'  => 'sometimes|string|max:100',
            'max_retries'              => 'sometimes|integer|min:1|max:10',
            'max_tokens'               => 'sometimes|integer|min:256|max:8192',
            'timeout_seconds'          => 'sometimes|integer|min:5|max:120',
        ]);

        $envMap = [
            'primary_provider'        => 'AI_PRIMARY_PROVIDER',
            'fallback_provider'       => 'AI_FALLBACK_PROVIDER',
            'openai_api_key'          => 'OPENAI_API_KEY',
            'openai_default_model'    => 'OPENAI_DEFAULT_MODEL',
            'openai_evaluation_model' => 'OPENAI_EVALUATION_MODEL',
            'gemini_api_key'          => 'GEMINI_API_KEY',
            'gemini_default_model'    => 'GEMINI_DEFAULT_MODEL',
            'gemini_evaluation_model' => 'GEMINI_EVALUATION_MODEL',
            'max_retries'             => 'AI_MAX_RETRIES',
            'max_tokens'              => 'AI_MAX_TOKENS',
            'timeout_seconds'         => 'AI_TIMEOUT_SECONDS',
        ];

        $envPath    = base_path('.env');
        $envContent = file_get_contents($envPath);

        foreach ($data as $key => $value) {
            if (!isset($envMap[$key]) || $value === null) continue;

            $envKey = $envMap[$key];
            $envValue = is_string($value) && str_contains($value, ' ')
                ? "\"{$value}\""
                : (string) $value;

            // Replace if exists, append if not
            if (preg_match("/^{$envKey}=/m", $envContent)) {
                $envContent = preg_replace(
                    "/^{$envKey}=.*/m",
                    "{$envKey}={$envValue}",
                    $envContent
                );
            } else {
                $envContent .= "\n{$envKey}={$envValue}";
            }
        }

        file_put_contents($envPath, $envContent);

        // Clear config cache
        Artisan::call('config:clear');

        return response()->json(['message' => 'Konfigurasi berhasil disimpan.']);
    }

    // ── Prompt Templates ─────────────────────────────────────────────────────

    public function listPrompts(): JsonResponse
    {
        return response()->json([
            'data' => AiPromptTemplate::orderBy('type')->orderByDesc('id')->get(),
        ]);
    }

    public function storePrompt(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'required|string|max:100',
            'type'          => 'required|in:job_analyzer,interviewer,evaluator,skill_gap,recommender,coach',
            'version'       => 'required|string|max:20',
            'system_prompt' => 'required|string',
            'is_active'     => 'boolean',
        ]);

        // If setting this one active, deactivate others of same type
        if (!empty($data['is_active'])) {
            AiPromptTemplate::where('type', $data['type'])->update(['is_active' => false]);
        }

        $prompt = AiPromptTemplate::create($data);

        return response()->json(['data' => $prompt], 201);
    }

    public function showPrompt(AiPromptTemplate $prompt): JsonResponse
    {
        return response()->json(['data' => $prompt]);
    }

    public function updatePrompt(Request $request, AiPromptTemplate $prompt): JsonResponse
    {
        $data = $request->validate([
            'name'          => 'sometimes|string|max:100',
            'version'       => 'sometimes|string|max:20',
            'system_prompt' => 'sometimes|string',
            'is_active'     => 'sometimes|boolean',
        ]);

        // Activating this prompt → deactivate siblings
        if (isset($data['is_active']) && $data['is_active']) {
            AiPromptTemplate::where('type', $prompt->type)
                ->where('id', '!=', $prompt->id)
                ->update(['is_active' => false]);
        }

        $prompt->update($data);

        return response()->json(['data' => $prompt->fresh()]);
    }

    public function destroyPrompt(AiPromptTemplate $prompt): JsonResponse
    {
        $prompt->delete();
        return response()->json(['message' => 'Prompt dihapus.']);
    }
}
