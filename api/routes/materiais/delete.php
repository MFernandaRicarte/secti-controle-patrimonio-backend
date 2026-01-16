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
$stmt = $pdo->prepare('SELECT id FROM itens_estoque WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Material não encontrado'], 404);
}

$stmt = $pdo->prepare('DELETE FROM itens_estoque WHERE id = ?');
$stmt->execute([$id]);

json(['ok' => true]);