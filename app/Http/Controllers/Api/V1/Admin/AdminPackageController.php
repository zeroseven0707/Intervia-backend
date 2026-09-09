<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Package;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminPackageController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json([
            'data' => Package::orderBy('sort_order')->orderBy('price')->get(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'required|string|max:100',
            'slug'             => 'nullable|string|max:100|unique:packages,slug',
            'description'      => 'nullable|string',
            'type'             => 'required|in:session,subscription',
            'session_count'    => 'required_if:type,session|nullable|integer|min:1',
            'duration_days'    => 'required_if:type,subscription|nullable|integer|min:1',
            'price'            => 'required|numeric|min:1000',
            'discounted_price' => 'nullable|numeric|min:1000|lt:price',
            'is_active'        => 'boolean',
            'is_popular'       => 'boolean',
            'sort_order'       => 'integer|min:0',
            'features'         => 'nullable|array',
            'features.*'       => 'string',
        ]);

        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $package = Package::create($data);

        return response()->json([
            'data'    => $package,
            'message' => 'Paket dibuat.',
        ], 201);
    }

    public function show(Package $package): JsonResponse
    {
        return response()->json(['data' => $package]);
    }

    public function update(Request $request, Package $package): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:100',
            'slug'             => 'sometimes|string|max:100|unique:packages,slug,' . $package->id,
            'description'      => 'nullable|string',
            'type'             => 'sometimes|in:session,subscription',
            'session_count'    => 'sometimes|nullable|integer|min:1',
            'duration_days'    => 'sometimes|nullable|integer|min:1',
            'price'            => 'sometimes|numeric|min:1000',
            'discounted_price' => 'nullable|numeric|min:1000',
            'is_active'        => 'boolean',
            'is_popular'       => 'boolean',
            'sort_order'       => 'integer|min:0',
            'features'         => 'nullable|array',
            'features.*'       => 'string',
        ]);

        if (isset($data['name']) && !isset($data['slug'])) {
            $data['slug'] = Str::slug($data['name']);
        }

        $package->update($data);

        return response()->json([
            'data'    => $package->fresh(),
            'message' => 'Paket diperbarui.',
        ]);
    }

    public function destroy(Package $package): JsonResponse
    {
        $package->delete();
        return response()->json(['message' => 'Paket dihapus.']);
    }
}
