<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido'], 405);
}

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

try {
    $pdo = db();

    $st = $pdo->prepare("SELECT id FROM tipos_materiais_consumo WHERE id = ?");
    $st->execute([$id]);
    if (!$st->fetch()) {
        json(['error' => 'Registro não encontrado'], 404);
    }

    $del = $pdo->prepare("DELETE FROM tipos_materiais_consumo WHERE id = ?");
    $del->execute([$id]);

    json(['ok' => true]);
} catch (Throwable $e) {
    json(['error' => 'Erro no servidor: '.$e->getMessage()], 500);
}