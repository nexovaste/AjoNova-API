<?php

namespace App\Http\Middleware;

use App\Models\Admin\UserDevice;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TrustDeviceMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $deviceId = $request->header('X-Device-ID');
        $token = $request->user()->currentAccessToken();

        if (!$deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Device ID is required.'
            ], 403);
        }

        $device = UserDevice::where('device_id', $deviceId)
            ->whereNotNull('verified_at')
            ->first();

        if (!$device) {
            return response()->json([
                'success' => false,
                'message' => 'Device is not trusted.'
            ], 403);
        }

        if ($token && $token->device_id !== $deviceId) {
            return response()->json([
                'success' => false,
                'message' => 'Token not valid for this device'
            ], 401);
        }
        return $next($request);
    }
}
