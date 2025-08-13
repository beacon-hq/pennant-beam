<?php

declare(strict_types=1);

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Illuminate\Support\Facades\Config;

beforeEach(fn () => Config::set('pennant.default', 'array'));

it('issues a JWT cookie via /beam/token route', function () {
    $response = $this->get('/beam/token');

    $response->assertNoContent();

    $response->assertCookie('BEAM-TOKEN');

    $jwt = $response->getCookie('BEAM-TOKEN', false)->getValue();
    JWT::decode($jwt, new Key(base64_decode(substr(\config('app.key'), 7)), 'HS256'));
});
