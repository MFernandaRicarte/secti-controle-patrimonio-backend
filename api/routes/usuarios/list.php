<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/auth.php';

cors();
requireSuperAdmin();

try {
    $pdo = db();

    $sql = "
      SELECT
        u.id,
        u.matricula,
        u.email,
        u.nome,
        u.perfil_id,
        p.nome AS perfil_nome,
        u.criado_em,
        u.data_nascimento,
        u.celular,
        u.cep,
        u.bairro,
        u.complemento,
        u.numero,
        u.cidade
      FROM usuarios u
      LEFT JOIN perfis p ON p.id = u.perfil_id
      ORDER BY u.id DESC
    ";

    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll();
    json($usuarios);

} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}