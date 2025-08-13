<?php

declare(strict_types=1);

namespace Beacon\PennantBeam\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cookie;
use Symfony\Component\HttpFoundation\Response;

class EnsureBeamJwt
{
    /**
     * Validate an existing JWT from the cookie, or issue a new one and store it ONLY in a cookie.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $cookieName = (string) config('pennant.beam.cookie_name', 'BEAM-TOKEN');
        $token = Cookie::get($cookieName);
        $valid = is_string($token) && $token !== '' ? $this->isJwtValid($token) : false;
        $shouldIssue = false;

        if (!$valid) {
            $token = $this->createJwt();
            $shouldIssue = true;
        }

        $response = $next($request);

        if ($shouldIssue) {
            $ttlSeconds = (int) config('pennant.beam.jwt.ttl', 300);
            $cookie = Cookie::make($cookieName, $token, (int) ceil($ttlSeconds / 60), httpOnly: false);
            $response->headers->setCookie($cookie);
        }

        return $response;
    }

    protected function createJwt(): string
    {
        $now = time();
        $ttl = (int) (config('pennant.beam.jwt.ttl', 300)); // five minutes default
        $iss = config('app.url', 'http://localhost');

        $payload = [
            'iss' => $iss,
            'iat' => $now,
            'exp' => $now + $ttl,
            'sub' => 'beam-guest',
        ];

        $secret = $this->jwtSecret();

        return JWT::encode($payload, $secret, 'HS256');
    }

    protected function isJwtValid(string $jwt): bool
    {
        try {
            $secret = $this->jwtSecret();
            // Validate signature and time-based claims (exp, nbf, iat)
            JWT::decode($jwt, new Key(is_string($secret) ? $secret : (string) $secret, 'HS256'));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Get the HMAC secret, decoding base64 APP_KEY if needed.
     */
    protected function jwtSecret(): string
    {
        $secret = (string) config('app.key');

        if (str_starts_with($secret, 'base64:')) {
            $decoded = base64_decode(substr($secret, 7), true);
            if ($decoded !== false) {
                $secret = $decoded;
            }
        }

        return $secret;
    }
}
