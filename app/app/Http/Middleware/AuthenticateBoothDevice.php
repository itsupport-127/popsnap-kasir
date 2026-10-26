<?php

namespace App\Http\Middleware;

use App\Models\BoothDevice;
use Closure;
use Illuminate\Http\Request;

class AuthenticateBoothDevice
{
    public function handle(Request $request, Closure $next)
    {
        $token = $request->bearerToken();
        $device = $token ? BoothDevice::where('token_hash', BoothDevice::hashToken($token))->where('is_active', true)->first() : null;

        if (! $device) {
            return response()->json(['message' => 'Token perangkat tidak valid.'], 401);
        }

        $device->forceFill(['last_seen_at' => now(), 'last_ip' => $request->ip()])->saveQuietly();
        $request->attributes->set('booth_device', $device);

        return $next($request);
    }
}
