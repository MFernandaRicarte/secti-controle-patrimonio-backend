<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../config/config.php';

cors();

try {
    $pdo = db();
    $sql = "SELECT id, matricula, email, nome, perfil_id, criado_em FROM usuarios ORDER BY id DESC";
    $stmt = $pdo->query($sql);
    $usuarios = $stmt->fetchAll();
    json($usuarios);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}
