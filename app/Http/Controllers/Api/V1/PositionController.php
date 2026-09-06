<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Position;
use Illuminate\Http\JsonResponse;

class PositionController extends Controller
{
    /**
     * GET /api/v1/positions
     */
    public function index(): JsonResponse
    {
        $positions = Position::active()
            ->with(['skills' => fn($q) => $q->active()])
            ->orderBy('name')
            ->get()
            ->map(fn($p) => [
                'id'          => $p->id,
                'name'        => $p->name,
                'slug'        => $p->slug,
                'description' => $p->description,
                'skills'      => $p->skills->map(fn($s) => [
                    'id'         => $s->id,
                    'name'       => $s->name,
                    'importance' => $s->pivot->importance,
                ]),
            ]);

        return response()->json(['data' => $positions]);
    }
}
