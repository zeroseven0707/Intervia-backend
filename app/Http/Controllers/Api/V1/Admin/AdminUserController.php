<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Enums\UserRole;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = User::withCount('interviewSessions')
            ->orderByDesc('created_at');

        if ($search = $request->query('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        if ($role = $request->query('role')) {
            $query->where('role', $role);
        }

        return response()->json($query->paginate(20));
    }

    public function show(User $user): JsonResponse
    {
        return response()->json([
            'data' => $user->load(['interviewSessions' => fn($q) => $q->latest()->limit(5)])
                           ->loadCount('interviewSessions'),
        ]);
    }

    public function update(Request $request, User $user): JsonResponse
    {
        $data = $request->validate([
            'name'             => 'sometimes|string|max:100',
            'email'            => 'sometimes|email|unique:users,email,' . $user->id,
            'role'             => 'sometimes|in:user,admin',
            'experience_level' => 'nullable|in:junior,mid,senior,lead',
        ]);

        $user->update($data);

        return response()->json(['data' => $user->fresh()]);
    }

    public function destroy(User $user): JsonResponse
    {
        // Prevent deleting yourself
        if ($user->id === auth()->id()) {
            return response()->json(['message' => 'Cannot delete your own account.'], 422);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted.']);
    }

    public function stats(): JsonResponse
    {
        return response()->json([
            'data' => [
                'total_users'      => User::count(),
                'admin_count'      => User::where('role', 'admin')->count(),
                'new_this_month'   => User::whereMonth('created_at', now()->month)->count(),
                'total_sessions'   => \App\Models\InterviewSession::count(),
                'completed_sessions' => \App\Models\InterviewSession::where('status', 'completed')->count(),
                'total_positions'  => \App\Models\Position::count(),
                'total_skills'     => \App\Models\Skill::count(),
                'pending_sources'  => \App\Models\LearningSource::where('status', 'pending')->count(),
            ],
        ]);
    }
}
