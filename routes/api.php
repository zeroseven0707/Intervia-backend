<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\InterviewSessionController;
use App\Http\Controllers\Api\V1\InterviewQuestionController;
use App\Http\Controllers\Api\V1\InterviewReportController;
use App\Http\Controllers\Api\V1\JobAnalyzerController;
use App\Http\Controllers\Api\V1\LearningController;
use App\Http\Controllers\Api\V1\PositionController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\Admin\AdminPositionController;
use App\Http\Controllers\Api\V1\Admin\AdminSkillController;
use App\Http\Controllers\Api\V1\Admin\AdminSourceController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminAiController;

/*
|--------------------------------------------------------------------------
| API Routes — /api/v1
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ── Public ────────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function () {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login',    [AuthController::class, 'login']);
    });

    Route::get('positions', [PositionController::class, 'index']);

    // ── Authenticated ──────────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function () {

        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me',      [AuthController::class, 'me']);

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Profile
        Route::get('profile',           [ProfileController::class, 'show']);
        Route::put('profile',           [ProfileController::class, 'update']);
        Route::put('profile/password',  [ProfileController::class, 'updatePassword']);

        // Job Analyzer
        Route::post('analyze-job', [JobAnalyzerController::class, 'analyze']);

        // Interview Sessions
        Route::apiResource('sessions', InterviewSessionController::class)
            ->only(['index', 'store', 'show', 'destroy']);

        // Interview flow (nested under session)
        Route::prefix('sessions/{session}')->group(function () {
            Route::get('next-question',  [InterviewQuestionController::class, 'next']);
            Route::post('answer',        [InterviewQuestionController::class, 'submitAnswer']);
            Route::get('report',         [InterviewReportController::class, 'show']);
        });

        // Learning Recommendations
        Route::get('learning/recommendations',     [LearningController::class, 'recommendations']);
        Route::get('learning/sessions/{session}',  [LearningController::class, 'forSession']);
    });

    // ── Admin ──────────────────────────────────────────────────────────────
    Route::middleware(['auth:sanctum', 'role:admin'])->prefix('admin')->group(function () {
        Route::apiResource('positions', AdminPositionController::class);
        Route::post('positions/{position}/skills',        [AdminPositionController::class, 'attachSkill']);
        Route::delete('positions/{position}/skills/{skill}', [AdminPositionController::class, 'detachSkill']);
        Route::put('positions/{position}/skills',         [AdminPositionController::class, 'syncSkills']);

        Route::apiResource('skills',    AdminSkillController::class);
        Route::apiResource('sources',   AdminSourceController::class);

        // User management
        Route::get('stats',               [AdminUserController::class, 'stats']);
        Route::get('users',               [AdminUserController::class, 'index']);
        Route::get('users/{user}',        [AdminUserController::class, 'show']);
        Route::put('users/{user}',        [AdminUserController::class, 'update']);
        Route::delete('users/{user}',     [AdminUserController::class, 'destroy']);

        // AI management
        Route::get('ai/config',                    [AdminAiController::class, 'getConfig']);
        Route::put('ai/config',                    [AdminAiController::class, 'updateConfig']);
        Route::get('ai/prompts',                   [AdminAiController::class, 'listPrompts']);
        Route::post('ai/prompts',                  [AdminAiController::class, 'storePrompt']);
        Route::get('ai/prompts/{prompt}',          [AdminAiController::class, 'showPrompt']);
        Route::put('ai/prompts/{prompt}',          [AdminAiController::class, 'updatePrompt']);
        Route::delete('ai/prompts/{prompt}',       [AdminAiController::class, 'destroyPrompt']);
    });
});
