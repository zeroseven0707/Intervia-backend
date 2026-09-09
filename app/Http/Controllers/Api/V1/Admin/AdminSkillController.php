<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Skill;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminSkillController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(['data' => Skill::withCount('positions')->orderBy('category')->orderBy('name')->get()]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'required|string|max:100|unique:skills,name',
            'category'    => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        $data['slug'] = Str::slug($data['name']);

        return response()->json(['data' => Skill::create($data)], 201);
    }

    public function show(Skill $skill): JsonResponse
    {
        return response()->json(['data' => $skill]);
    }

    public function update(Request $request, Skill $skill): JsonResponse
    {
        $data = $request->validate([
            'name'        => 'sometimes|string|max:100|unique:skills,name,' . $skill->id,
            'category'    => 'nullable|string|max:50',
            'description' => 'nullable|string',
            'is_active'   => 'boolean',
        ]);
        if (isset($data['name'])) $data['slug'] = Str::slug($data['name']);
        $skill->update($data);

        return response()->json(['data' => $skill]);
    }

    public function destroy(Skill $skill): JsonResponse
    {
        $skill->delete();
        return response()->json(['message' => 'Deleted.']);
    }
}
