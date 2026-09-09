<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        // $user->role bisa jadi: Enum UserRole (dari casts) ATAU string (jika casts tidak aktif)
        // Kita NORMALISASI ke string value untuk dibandingkan
        $userRole = $user->role instanceof UserRole ? $user->role->value : (string) $user->role;

        // Normalisasi parameter $roles juga (untuk backward compat)
        $allowed = array_map(static function ($r) {
            if ($r instanceof UserRole) return $r->value;
            return (string) $r;
        }, $roles);

        if (!in_array($userRole, $allowed, true)) {
            return response()->json(['message' => 'Forbidden.'], 403);
        }

        return $next($request);
    }
}
