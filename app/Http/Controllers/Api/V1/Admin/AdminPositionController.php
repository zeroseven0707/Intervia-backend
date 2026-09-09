<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\Skill;


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

    public function attachSkill(Request $request, Position $position): JsonResponse
    {
        $data = $request->validate([
            'skill_id'   => 'required|exists:skills,id',
            'importance' => 'required|in:required,preferred',
        ]);

        $position->skills()->attach($data['skill_id'], ['importance' => $data['importance']]);

        return response()->json([
            'data'    => $position->load('skills'),
            'message' => 'Skill attached.',
        ]);
    }

    public function detachSkill(Position $position, Skill $skill): JsonResponse
    {
        $position->skills()->detach($skill->id);

        return response()->json([
            'data'    => $position->load('skills'),
            'message' => 'Skill detached.',
        ]);
    }

    public function syncSkills(Request $request, Position $position): JsonResponse
    {
        $data = $request->validate([
            'skills'            => 'required|array',
            'skills.*.skill_id' => 'required|exists:skills,id',
            'skills.*.importance' => 'required|in:required,preferred',
        ]);

        $syncData = [];
        foreach ($data['skills'] as $item) {
            $syncData[$item['skill_id']] = ['importance' => $item['importance']];
        }

        $position->skills()->sync($syncData);

        return response()->json([
            'data'    => $position->load('skills'),
            'message' => 'Skills synced.',
        ]);
    }
}
