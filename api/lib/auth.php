<?php
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/db.php';

/**
 * Autenticação simples via header X-User-Id (padrão do projeto).
 * O frontend salva secti_user no localStorage e envia X-User-Id nas requisições.
 */

function getAuthUserId(): ?int {
    $raw = $_SERVER['HTTP_X_USER_ID'] ?? '';
    if (!is_string($raw)) return null;

    $raw = trim($raw);
    if ($raw === '' || !ctype_digit($raw)) return null;

    return (int)$raw;
}

function authUser(): array {
    $uid = getAuthUserId();

    if (!$uid) {
        json(['error' => 'Não autenticado.'], 401);
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
    $st->execute([$uid]);
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