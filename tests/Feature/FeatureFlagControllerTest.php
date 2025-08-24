<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Config;
use Illuminate\Testing\Fluent\AssertableJson;
use Laravel\Pennant\Feature;

beforeEach(fn () => Config::set('pennant.default', 'array'));

it('returns the feature status and value from Pennant', function () {
    $feature = 'test-flag';

    Feature::define($feature, function () {
        return 'testing';
    });


    $payload = ['userId' => 123, 'segment' => 'canary'];

    // Generate a valid JWT for Authorization header
    $now = time();
    $ttl = (int) (config('beam.jwt_ttl', 3600));
    $claims = [
        'iss' => config('app.url', 'http://localhost'),
        'iat' => $now,
        'exp' => $now + $ttl,
        'sub' => 'beam-guest',
    ];
    $secret = (string) config('app.key');
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
        fn (AssertableJson $json) =>
        $json->where('featureFlag', $feature)
             ->where('status', true)
             ->where('value', 'testing')
    );
});

it('handles disabled feature and null value', function () {
    $feature = 'test-flag';

    Feature::define($feature, function () {
        return false;
    });

    $payload = ['accountId' => 42];

    // Generate a valid JWT for Authorization header
    $now = time();
    $ttl = (int) (config('beam.jwt_ttl', 500));
    $claims = [
        'iss' => config('app.url', 'http://localhost'),
        'iat' => $now,
        'exp' => $now + $ttl,
        'sub' => 'beam-guest',
    ];
    $secret = (string) config('app.key');
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
        fn (AssertableJson $json) =>
        $json->where('featureFlag', $feature)
             ->where('status', false)
             ->where('value', null)
    );
});

it('responds to OPTIONS requests with CORS headers', function () {
    $feature = 'test-flag';

    // Generate a valid JWT for Authorization header
    $now = time();
    $ttl = (int) (config('beam.jwt_ttl', 500));
    $claims = [
        'iss' => config('app.url', 'http://localhost'),
        'iat' => $now,
        'exp' => $now + $ttl,
        'sub' => 'beam-guest',
    ];
    $secret = (string) config('app.key');
    if (str_starts_with($secret, 'base64:')) {
        $decoded = base64_decode(substr($secret, 7), true);
        if ($decoded !== false) {
            $secret = $decoded;
        }
    }
    $token = JWT::encode($claims, $secret, 'HS256');

    // Set Origin header to test CORS behavior
    $response = $this->withHeaders([
        'Authorization' => 'Bearer ' . $token,
        'Origin' => config('app.url', 'http://localhost'),
    ])->options("/beam/feature-flag/{$feature}");

    $response->assertStatus(204);
    $response->assertHeader('Access-Control-Allow-Methods', 'POST, OPTIONS');
    $response->assertHeader('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With');
    $response->assertHeader('Access-Control-Allow-Credentials', 'true');
    $response->assertHeader('Vary', 'Origin');
    $response->assertHeader('Access-Control-Allow-Origin', config('app.url', 'http://localhost'));
});
