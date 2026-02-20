<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/auth.php';

cors();
$user = requireAuth();
$perfil = strtoupper($user['perfil_nome'] ?? '');

if (!in_array($perfil, ['SUPERADMIN', 'ADMINISTRADOR', 'ADMIN_LANHOUSE'])) {
    json(['error' => 'Acesso negado.'], 403);
}

try {
    $pdo = db();

    $where = '';
    $params = [];

    if ($perfil === 'ADMIN_LANHOUSE') {
        $where = " WHERE UPPER(p.nome) IN ('PROFESSOR', 'ADMIN_LANHOUSE')";
    }

    $sql = "
      SELECT
        u.id,
        u.matricula,
        u.email,
        u.nome,
        u.perfil_id,
        p.nome AS perfil_nome,
        u.criado_em
      FROM usuarios u
      LEFT JOIN perfis p ON p.id = u.perfil_id
      {$where}
      ORDER BY u.id DESC
    ";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll();
    json($usuarios);

} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}