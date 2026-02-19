<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use App\Models\Subdomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('X-Api-Key') ?? $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'API key is required.'], Response::HTTP_UNAUTHORIZED);
        }

        $apiKey = ApiKey::query()
            ->with('warehouse')
            ->where('key_hash', hash('sha256', $token))
            ->first();

        if (! $apiKey || ! $apiKey->is_active) {
            return response()->json(['message' => 'Invalid API key.'], Response::HTTP_UNAUTHORIZED);
        }

        if ($apiKey->expires_at && $apiKey->expires_at->isPast()) {
            return response()->json(['message' => 'API key expired.'], Response::HTTP_UNAUTHORIZED);
        }

        /** @var Subdomain|null $currentSubdomain */
        $currentSubdomain = app()->has('current_subdomain') ? app('current_subdomain') : null;

        if (! $currentSubdomain || ! $apiKey->warehouse || $apiKey->warehouse->subdomain_id !== $currentSubdomain->id) {
            return response()->json(['message' => 'Invalid API key.'], Response::HTTP_UNAUTHORIZED);
        }

        $request->attributes->set('api_key', $apiKey);
        $request->attributes->set('warehouse', $apiKey->warehouse);

        $apiKey->forceFill(['last_used_at' => now()])->save();

        return $next($request);
    }
}
