<?php
require_once __DIR__ .'/../lib/http.php';
require_once __DIR__ .'/../config/config.php';

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

    $del = $pdo->prepare("DELETE FROM setores WHERE id = ?");
    $del->execute([$id]);

    json(['success' => true]);
} catch (Throwable $e) {
    json(['error' => $e->getMessage()], 500);
}