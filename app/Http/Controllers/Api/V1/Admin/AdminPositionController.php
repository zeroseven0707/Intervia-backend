<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPositionController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Position::withCount('skills')->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:positions,name',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        $data['slug'] = Str::slug($data['name']);

        return response()->json(['data' => Position::create($data)], 201);
    }

    public function show(Position $position): JsonResponse
    {
        return response()->json(['data' => $position->load('skills')]);
    }

    public function update(Request $request, Position $position): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:positions,name,' . $position->id,
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $position->update($data);

        return response()->json(['data' => $position]);
    }

    public function destroy(Position $position): JsonResponse
    {
        $position->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
