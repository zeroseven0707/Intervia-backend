<?php

return [
    /*
    |--------------------------------------------------------------------------
    | AI Provider Configuration
    |--------------------------------------------------------------------------
    | primary   : provider used by default
    | fallback  : provider used when primary fails
    */

    'primary_provider'  => env('AI_PRIMARY_PROVIDER', 'openai'),
    'fallback_provider' => env('AI_FALLBACK_PROVIDER', 'gemini'),

    'providers' => [
        'openai' => [
            'api_key'          => env('OPENAI_API_KEY'),
            'default_model'    => env('OPENAI_DEFAULT_MODEL', 'gpt-4o-mini'),
            'evaluation_model' => env('OPENAI_EVALUATION_MODEL', 'gpt-4o'),
        ],
        'gemini' => [
            'api_key'          => env('GEMINI_API_KEY'),
            'default_model'    => env('GEMINI_DEFAULT_MODEL', 'gemini-1.5-flash'),
            'evaluation_model' => env('GEMINI_EVALUATION_MODEL', 'gemini-1.5-pro'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Model Assignment per Task
    |--------------------------------------------------------------------------
    | Use lighter models for classification tasks.
    | Use stronger models for nuanced evaluation.
    */

    'task_models' => [
        'job_analyzer'    => 'default',    // lightweight classification
        'interviewer'     => 'default',    // question generation
        'evaluator'       => 'evaluation', // needs strong reasoning
        'skill_gap'       => 'default',
        'recommender'     => 'default',
        'coach'           => 'evaluation',
    ],

    /*
    |--------------------------------------------------------------------------
    | Safety Limits
    |--------------------------------------------------------------------------
    */

    'max_retries'       => env('AI_MAX_RETRIES', 3),
    'timeout_seconds'   => env('AI_TIMEOUT_SECONDS', 30),
    'max_tokens'        => env('AI_MAX_TOKENS', 2048),
    'rate_limit_per_min'=> env('AI_RATE_LIMIT_PER_MIN', 30),
];
