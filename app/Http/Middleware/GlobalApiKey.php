<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class GlobalApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $providedKey = $request->header('X-API-KEY');
        $validKey = config('app.key');

        if (!$providedKey || $providedKey !== $validKey) {
            return response()->json(
                [
                    'success'  => false,
                    'message' => 'Unauthorized. Invalid API Key'
                ],401);
        }
        return $next($request);
    }
}
