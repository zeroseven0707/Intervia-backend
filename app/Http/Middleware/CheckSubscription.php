<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckSubscription
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        if (!$user->canStartInterview()) {
            return response()->json([
                'message' => 'Subscription atau kredit tidak mencukupi. Silakan lakukan pembayaran terlebih dahulu.',
                'code'    => 'PAYMENT_REQUIRED',
            ], 402);
        }

        return $next($request);
    }
}
