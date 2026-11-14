<?php

namespace App\Http\Middleware;

use App\Models\Device;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateDevice
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! $token) {
            return response()->json([
                'message' => 'Token tidak ditemukan.',
                'code' => 'unauthorized',
            ], 401);
        }

        $tokenHash = hash('sha256', $token);
        $device = Device::where('token_hash', $tokenHash)->first();

        if (! $device || ! hash_equals($device->token_hash, $tokenHash)) {
            return response()->json([
                'message' => 'Token alat tidak valid.',
                'code' => 'unauthorized',
            ], 401);
        }

        // Throttle writing last_seen_at: only if null or older than 30 seconds
        if (! $device->last_seen_at || $device->last_seen_at->diffInSeconds(now()) >= 30) {
            $device->update([
                'last_seen_at' => now(),
                'last_ip' => $request->ip(),
            ]);
        }

        $request->attributes->set('device', $device);

        return $next($request);
    }
}

