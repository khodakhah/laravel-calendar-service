<?php

namespace App\Http\Middleware;

use App\Models\ApiKey;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateApiKey
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->bearerToken();

        if (! is_string($token) || ! preg_match('/^lcs_([0-9a-f-]{36})\.([A-Za-z0-9_-]{43})$/', $token, $matches)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiKey = ApiKey::query()->active()->find($matches[1]);

        if ($apiKey === null || ! Hash::check($matches[2], $apiKey->token_hash)) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        $apiKey->forceFill(['last_used_at' => now()])->save();
        $request->attributes->set('apiKey', $apiKey);

        return $next($request);
    }
}
