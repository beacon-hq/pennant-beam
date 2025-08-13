<?php

declare(strict_types=1);

namespace Beacon\PennantBeam\Http\Middleware;

use Closure;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureBeamAuthenticated
{
    public function __construct(protected AuthFactory $auth)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $headers = $this->corsHeaders($request);

        if (strtoupper($request->getMethod()) === 'OPTIONS') {
            $response = response('', 204);
            foreach ($headers as $key => $value) {
                $response->headers->set($key, $value);
            }

            return $response;
        }

        if ($request->hasHeader('Authorization')) {
            $jwt = $request->bearerToken();
            if ($jwt !== '' && $this->isJwtValid($jwt)) {
                /** @var Response $response */
                $response = $next($request);
                // Attach CORS headers to the normal response
                foreach ($headers as $key => $value) {
                    // Don't override if already set by global/standard CORS middleware
                    if (!$response->headers->has($key)) {
                        $response->headers->set($key, $value);
                    }
                }

                return $response;
            }
        }

        $unauth = response()->json(['message' => 'Unauthenticated.'], 401);
        foreach ($headers as $key => $value) {
            if (!$unauth->headers->has($key)) {
                $unauth->headers->set($key, $value);
            }
        }

        return $unauth;
    }

    protected function isJwtValid(string $jwt): bool
    {
        try {
            $secret = $this->jwtSecret();
            JWT::decode($jwt, new Key(is_string($secret) ? $secret : (string) $secret, 'HS256'));

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

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

    protected function corsHeaders(Request $request): array
    {
        $origin = $request->headers->get('Origin');
        $allowedOrigin = $this->allowedOrigin($request, $origin);

        $headers = [
            'Vary' => 'Origin',
            'Access-Control-Allow-Credentials' => 'true',
            'Access-Control-Allow-Methods' => 'POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Authorization, X-Requested-With',
        ];

        if ($allowedOrigin !== null) {
            $headers['Access-Control-Allow-Origin'] = $allowedOrigin;
        }

        return $headers;
    }

    protected function allowedOrigin(Request $request, ?string $origin): ?string
    {
        if ($origin === null || $origin === '') {
            return null;
        }

        $originHost = parse_url($origin, PHP_URL_HOST);
        $originScheme = parse_url($origin, PHP_URL_SCHEME);
        if (!is_string($originHost) || $originHost === '') {
            return null;
        }

        $forwardedProto = $request->headers->get('X-Forwarded-Proto');
        $forwardedHost = $request->headers->get('X-Forwarded-Host');

        $externalScheme = $forwardedProto ?: $request->getScheme();
        $externalHost = $forwardedHost ?: $request->getHost();

        $appUrl = (string) config('app.url', '');
        $appUrlHost = $appUrl !== '' ? parse_url($appUrl, PHP_URL_HOST) : null;

        $matchesExternal = is_string($externalHost) && $externalHost !== '' && strcasecmp($originHost, $externalHost) === 0;
        $matchesAppUrl = is_string($appUrlHost) && $appUrlHost !== '' && strcasecmp($originHost, (string) $appUrlHost) === 0;

        if ($matchesExternal || $matchesAppUrl) {
            // If proxy indicates HTTPS but Origin is HTTP, reject to avoid mixed-mode issues
            if ($forwardedProto && $originScheme && strcasecmp($forwardedProto, $originScheme) !== 0) {
                return null;
            }

            return $origin;
        }

        return null;
    }
}
