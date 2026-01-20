<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? 0;

if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM lhs_aulas WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Aula não encontrada'], 404);
}

$stmt = $pdo->prepare("DELETE FROM lhs_aulas WHERE id = ?");
$stmt->execute([$id]);

json(['ok' => true]);
