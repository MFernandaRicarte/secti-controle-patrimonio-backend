<?php
require __DIR__.'/../../lib/http.php';
require __DIR__.'/../../config/config.php';

cors();
if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido'], 405);
}

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) json(['error' => 'ID inválido'], 400);

try {
    $pdo = db();

    $st = $pdo->prepare('SELECT id FROM usuarios WHERE id = ?');
    $st->execute([$id]);
    if (!$st->fetch()) json(['error' => 'Usuário não encontrado'], 404);

    $del = $pdo->prepare('DELETE FROM usuarios WHERE id = ?');
    $del->execute([$id]);

    json(['deleted' => true, 'id' => $id]);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}