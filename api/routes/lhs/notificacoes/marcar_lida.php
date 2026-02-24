<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
}

$user = requireLhsAdmin();
$pdo = db();

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da notificação não informado.'], 400);
}

$stmt = $pdo->prepare("SELECT id FROM lhs_notificacoes WHERE id = ?");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Notificação não encontrada.'], 404);
}

$stmtUpdate = $pdo->prepare("UPDATE lhs_notificacoes SET lida = 1 WHERE id = ?");
$stmtUpdate->execute([$id]);

json([
    'ok' => true,
    'message' => 'Notificação marcada como lida.',
]);
