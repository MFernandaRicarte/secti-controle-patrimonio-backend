<?php

require_once __DIR__ . '/../config/jwt.config.php';

function base64url_encode($data) {
    return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
}

function base64url_decode($data) {
    return base64_decode(strtr($data, '-_', '+/'));
}

function jwt_generate(array $payload): string {
    $header = base64url_encode(json_encode([
        'alg' => 'HS256',
        'typ' => 'JWT'
    ]));

    $payload['exp'] = time() + JWT_EXPIRES_IN;

    $payloadEncoded = base64url_encode(json_encode($payload));

    $signature = hash_hmac(
        'sha256',
        "$header.$payloadEncoded",
        JWT_SECRET,
        true
    );

    $signatureEncoded = base64url_encode($signature);

    return "$header.$payloadEncoded.$signatureEncoded";
}

function jwt_verify(string $token): ?array {
    $parts = explode('.', $token);
    if (count($parts) !== 3) return null;

    [$header, $payload, $signature] = $parts;

    $check = base64url_encode(
        hash_hmac(
            'sha256',
            "$header.$payload",
            JWT_SECRET,
            true
        )
    );

    if (!hash_equals($check, $signature)) return null;

    $data = json_decode(base64url_decode($payload), true);
    if (!$data || ($data['exp'] ?? 0) < time()) return null;

    return $data;
}
