<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSourceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = (int) ($request->query('per_page', 20));
        $results = LearningSource::orderByDesc('created_at')->paginate($perPage);

        return response()->json([
            'data' => $results->items(),
            'meta' => [
                'current_page' => $results->currentPage(),
                'last_page'    => $results->lastPage(),
                'per_page'     => $results->perPage(),
                'total'        => $results->total(),
            ],
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'type'        => 'required|in:youtube,article,documentation,pdf',
            'title'       => 'required|string|max:255',
            'url'         => 'required|url|max:500',
            'publisher'   => 'nullable|string|max:100',
            'author'      => 'nullable|string|max:100',
            'language'    => 'nullable|string|max:10',
            'external_id' => 'nullable|string|max:100',
            'status'      => 'in:pending,approved,rejected,archived',
        ]);

        return response()->json(['data' => LearningSource::create($data)], 201);
    }

    public function show(LearningSource $source): JsonResponse
    {
        return response()->json(['data' => $source->load('materials')]);
    }

    public function update(Request $request, LearningSource $source): JsonResponse
    {
        $data = $request->validate([
            'type'        => 'sometimes|in:youtube,article,documentation,pdf',
            'title'       => 'sometimes|string|max:255',
            'url'         => 'sometimes|url|max:500',
            'publisher'   => 'nullable|string|max:100',
            'author'      => 'nullable|string|max:100',
            'language'    => 'nullable|string|max:10',
            'external_id' => 'nullable|string|max:100',
            'status'      => 'sometimes|in:pending,approved,rejected,archived',
            'metadata'    => 'sometimes|array',
        ]);
        $source->update($data);

        return response()->json(['data' => $source->fresh()]);
    }

    public function destroy(LearningSource $source): JsonResponse
    {
        $source->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
