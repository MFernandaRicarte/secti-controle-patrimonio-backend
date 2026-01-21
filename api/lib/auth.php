<?php
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/jwt.php';

function getBearerToken(): ?string {
    if (function_exists('getallheaders')) {
        $headers = getallheaders();
        if (isset($headers['Authorization'])) {
            if (preg_match('/Bearer\s+(.*)$/i', $headers['Authorization'], $m)) {
                return trim($m[1]);
            }
        }
    }

    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        if (preg_match('/Bearer\s+(.*)$/i', $_SERVER['HTTP_AUTHORIZATION'], $m)) {
            return trim($m[1]);
        }
    }

    return null;
}

function authUser(): array {
    $token = getBearerToken();

    if (!$token) {
        json(['error' => 'Token não informado'], 401);
    }

    $payload = jwt_verify($token);

    if (!$payload) {
        json(['error' => 'Token inválido ou expirado'], 401);
    }

    $pdo = db();
    $st = $pdo->prepare("
        SELECT 
            u.id, u.matricula, u.email, u.nome, u.perfil_id, u.criado_em,
            p.nome AS perfil_nome
        FROM usuarios u
        LEFT JOIN perfis p ON p.id = u.perfil_id
        WHERE u.id = ?
        LIMIT 1
    ");
    $st->execute([$payload['uid']]);
    $u = $st->fetch();

    if (!$u) {
        json(['error' => 'Usuário inválido'], 401);
    }

    return $u;
}

function requireAuth(): array {
    return authUser();
}

function requireSuperAdmin(): array {
    $u = authUser();
    if (($u['perfil_nome'] ?? null) !== 'SUPERADMIN') {
        json(['error' => 'Acesso negado. Requer SUPERADMIN'], 403);
    }
    return $u;
}
