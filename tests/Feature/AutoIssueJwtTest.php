<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Pennant\Feature;

beforeEach(fn () => Config::set('pennant.default', 'array'));

it('requires Authorization bearer token for guests and returns 401 when missing', function () {
    $feature = 'auto-jwt-flag';

    Feature::define($feature, function () {
        return true;
    });

    $payload = ['userId' => 1];

    $response = $this->postJson("/beam/feature-flag/{$feature}", $payload);

    $response->assertStatus(401);
});

it('accepts Authorization bearer token for guests', function () {
    $feature = 'auto-jwt-flag';

    Feature::define($feature, function () {
        return true;
    });

    $payload = ['userId' => 1];

    // Create a valid JWT using the same secret as middleware
    $now = time();
    $ttl = (int) (config('beam.jwt_ttl', 3600));
    $claims = [
        'iss' => config('app.url', 'http://localhost'),
        'iat' => $now,
        'exp' => $now + $ttl,
        'sub' => 'beam-guest',
    ];
    $secret = (string) config('app.key', 'beam-testing-secret');
    if (str_starts_with($secret, 'base64:')) {
        $decoded = base64_decode(substr($secret, 7), true);
        if ($decoded !== false) {
            $secret = $decoded;
        }
    }
    $token = JWT::encode($claims, $secret, 'HS256');

    $response = $this->withHeader('Authorization', 'Bearer ' . $token)
        ->postJson("/beam/feature-flag/{$feature}", $payload);

    $response->assertOk();

    $response->assertJson(
        fn (AssertableJson $json) => $json->where('featureFlag', $feature)
            ->where('status', true)
            ->etc()
    );
});
