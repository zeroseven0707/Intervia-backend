<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\LearningSource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminSourceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => LearningSource::orderByDesc('created_at')->paginate(20),
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
            'title'   => 'sometimes|string|max:255',
            'status'  => 'sometimes|in:pending,approved,rejected,archived',
            'author'  => 'nullable|string|max:100',
        ]);
        $source->update($data);

        return response()->json(['data' => $source]);
    }

    public function destroy(LearningSource $source): JsonResponse
    {
        $source->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
