<?php
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Method Not Allowed'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int)$id;
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id FROM bens_patrimoniais WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Bem não encontrado'], 404);
}

try {
    $stmt = $pdo->prepare('DELETE FROM bens_patrimoniais WHERE id = ?');
    $stmt->execute([$id]);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        json(['error' => 'Não é possível excluir: há movimentações vinculadas a este bem'], 409);
    }
    throw $e;
}

json(['ok' => true]);