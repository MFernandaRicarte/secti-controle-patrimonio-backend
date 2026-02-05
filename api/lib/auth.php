<?php
require_once __DIR__ . '/http.php';
require_once __DIR__ . '/db.php';

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

function requireAdminOrSuperAdmin(): array {
    $u = authUser();
    $perfil = strtoupper($u['perfil_nome'] ?? '');
    if ($perfil !== 'SUPERADMIN' && $perfil !== 'ADMINISTRADOR') {
        json(['error' => 'Acesso negado. Requer Administrador ou SUPERADMIN'], 403);
    }
    return $u;
}

function isProfessor(array $user): bool {
    return strtoupper($user['perfil_nome'] ?? '') === 'PROFESSOR';
}

function isAdmin(array $user): bool {
    $perfil = strtoupper($user['perfil_nome'] ?? '');
    return $perfil === 'SUPERADMIN' || $perfil === 'ADMINISTRADOR';
}

function requireProfessorOrAdmin(): array {
    $u = authUser();
    $perfil = strtoupper($u['perfil_nome'] ?? '');
    if (!in_array($perfil, ['SUPERADMIN', 'ADMINISTRADOR', 'PROFESSOR'])) {
        json(['error' => 'Acesso negado. Requer Professor ou Administrador'], 403);
    }
    return $u;
}

function professorPodeTurma(array $user, int $turmaId): bool {
    if (isAdmin($user)) {
        return true;
    }
    
    $pdo = db();
    $st = $pdo->prepare("SELECT professor_id FROM lhs_turmas WHERE id = ?");
    $st->execute([$turmaId]);
    $turma = $st->fetch();
    
    if (!$turma) {
        return false;
    }
    
    return (int)$turma['professor_id'] === (int)$user['id'];
}

function getTurmasProfessor(int $professorId): array {
    $pdo = db();
    $st = $pdo->prepare("SELECT id FROM lhs_turmas WHERE professor_id = ?");
    $st->execute([$professorId]);
    return array_column($st->fetchAll(), 'id');
}