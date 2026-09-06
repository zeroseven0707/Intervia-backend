<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\AI\JobAnalyzerService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Enums\ExperienceLevel;

class JobAnalyzerController extends Controller
{
    public function __construct(private JobAnalyzerService $analyzer) {}

    /**
     * POST /api/v1/analyze-job
     */
    public function analyze(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'job_description'  => ['required', 'string', 'min:50', 'max:10000'],
            'position'         => ['nullable', 'string', 'max:100'],
            'experience_level' => ['nullable', Rule::enum(ExperienceLevel::class)],
        ]);

        $result = $this->analyzer->analyze(
            $validated['job_description'],
            $validated['position'] ?? null,
            $validated['experience_level'] ?? null,
        );

        return response()->json(['data' => $result]);
    }
}
