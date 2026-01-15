<?php
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/db.php';

function getHeader(string $name): ?string {
    $key = 'HTTP_' . strtoupper(str_replace('-', '_', $name));
    return $_SERVER[$key] ?? null;
}

function authUser(): array {
    $userId = (int)(getHeader('X-User-Id') ?? 0);
    if ($userId <= 0) {
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
    $st->execute([$userId]);
    $u = $st->fetch();

    if (!$u) {
        json(['error' => 'Usuário inválido.'], 401);
    }

    return $u;
}

function requireAuth(): array {
    return authUser();
}

function requireSuperAdmin(): array {
    $u = authUser();
    if (($u['perfil_nome'] ?? null) !== 'SUPERADMIN') {
        json(['error' => 'Acesso negado. Requer SUPERADMIN.'], 403);
    }
    return $u;
}