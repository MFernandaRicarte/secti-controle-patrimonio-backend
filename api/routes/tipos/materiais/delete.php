<?php
require __DIR__.'/../../../lib/http.php';
require __DIR__.'/../../../config/config.php';

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

    $st = $pdo->prepare('DELETE FROM tipos_materiais_consumo WHERE id = ?');
    $st->execute([$id]);

    if ($st->rowCount() === 0) {
        json(['error' => 'Registro não encontrado'], 404);
    }

    json(['message' => 'Excluído com sucesso'], 200);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}