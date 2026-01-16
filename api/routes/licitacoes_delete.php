<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/http.php';

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

$stmt = $pdo->prepare('SELECT id FROM licitacoes WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Licitação não encontrada'], 404);
}

$stmt = $pdo->prepare('DELETE FROM licitacoes WHERE id = ?');
$stmt->execute([$id]);

json(['deleted' => true, 'id' => $id]);
