<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if (!in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'PATCH'], true)) {
    json(['error' => 'Método não permitido. Use PUT/PATCH.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'id inválido.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$newStatus = strtoupper(trim($input['status'] ?? ''));

$allowed = ['ABERTA', 'EM_TRIAGEM', 'EM_MANUTENCAO', 'PARA_DESCARTE', 'FINALIZADA', 'CANCELADA'];
if ($newStatus === '' || !in_array($newStatus, $allowed, true)) {
    json(['error' => 'Status inválido.', 'allowed' => $allowed], 422);
}

$stmt = $pdo->prepare("SELECT id FROM rct_os WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'OS não encontrada.'], 404);
}

$pdo->beginTransaction();

try {
    // Ajuste aqui se sua tabela usar nomes diferentes.
    $st = $pdo->prepare("
        UPDATE rct_os
        SET status = ?,
            atualizado_em = CURRENT_TIMESTAMP,
            atualizado_por = ?
        WHERE id = ?
    ");
    $st->execute([$newStatus, (int)$user['id'], $id]);

    $pdo->commit();

    json(['ok' => true, 'id' => $id, 'status' => $newStatus]);
} catch (PDOException $e) {
    $pdo->rollBack();
    json(['error' => 'Erro ao atualizar status da OS.'], 500);
}