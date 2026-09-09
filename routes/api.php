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
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminPositionController;
use App\Http\Controllers\Api\V1\Admin\AdminSkillController;
use App\Http\Controllers\Api\V1\Admin\AdminSourceController;
use App\Http\Controllers\Api\V1\Admin\AdminUserController;
use App\Http\Controllers\Api\V1\Admin\AdminAiController;
use App\Http\Controllers\Api\V1\Admin\AdminPaymentController;
use App\Http\Controllers\Api\V1\Admin\AdminPackageController;

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

    // Public payment info
    Route::get('payment/public-config',  [PaymentController::class, 'publicConfig']);
    Route::get('payment/packages',       [PaymentController::class, 'packages']);

    // Midtrans webhooks & redirects (no auth, Midtrans POST signature verified inside)
    Route::post('payment/midtrans/notification', [PaymentController::class, 'midtransNotification']);
    Route::get('payment/midtrans/finish',        [PaymentController::class, 'midtransFinish']);
    Route::get('payment/midtrans/unfinish',      [PaymentController::class, 'midtransUnfinish']);
    Route::get('payment/midtrans/error',         [PaymentController::class, 'midtransError']);

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

        // Payment (user side)
        Route::prefix('payment')->group(function () {
            Route::get('subscription-status',         [PaymentController::class, 'subscriptionStatus']);
            Route::post('checkout/package/{package}', [PaymentController::class, 'checkoutPackage']);
            Route::post('checkout/single',            [PaymentController::class, 'checkoutSingle']);
            Route::get('transactions',                [PaymentController::class, 'myTransactions']);
            Route::get('transactions/{transaction}',  [PaymentController::class, 'transactionStatus']);
        });

        // Interview Sessions (index, show, destroy: no subscription check; store + flow: need subscription)
        Route::apiResource('sessions', InterviewSessionController::class)
            ->only(['index', 'show', 'destroy']);

        Route::middleware('subscription')->group(function () {
            Route::post('sessions', [InterviewSessionController::class, 'store']);

            Route::prefix('sessions/{session}')->group(function () {
                Route::get('next-question',  [InterviewQuestionController::class, 'next']);
                Route::post('answer',        [InterviewQuestionController::class, 'submitAnswer']);
            });
        });

        // Session report (completed sessions, no need active sub)
        Route::get('sessions/{session}/report', [InterviewReportController::class, 'show']);

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
        Route::apiResource('packages',  AdminPackageController::class);

        // Payment admin
        Route::prefix('payment')->group(function () {
            Route::get('settings',                         [AdminPaymentController::class, 'getSettings']);
            Route::put('settings',                         [AdminPaymentController::class, 'updateSettings']);
            Route::get('summary',                          [AdminPaymentController::class, 'summary']);
            Route::get('transactions',                     [AdminPaymentController::class, 'transactions']);
            Route::get('transactions/{transaction}',       [AdminPaymentController::class, 'showTransaction']);
            Route::put('transactions/{transaction}/status', [AdminPaymentController::class, 'updateTransactionStatus']);
            Route::put('users/{user}/adjust-credit',       [AdminPaymentController::class, 'adjustUserCredit']);
        });

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
