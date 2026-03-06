<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'PATCH') {
    json(['error' => 'Método não permitido. Use PUT/PATCH.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'ID inválido.'], 400);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$status = strtoupper(trim($input['status'] ?? ''));

$allowed = ['ABERTA', 'TRIAGEM', 'AGENDADA', 'COLETADA', 'CANCELADA'];

if ($status === '' || !in_array($status, $allowed, true)) {
    json(['error' => 'Status inválido.', 'allowed' => $allowed], 422);
}

$stmt = $pdo->prepare("SELECT id, status, protocolo FROM rct_solicitacoes WHERE id = ? LIMIT 1");
$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json(['error' => 'Solicitação não encontrada.'], 404);
}

try {
    $upd = $pdo->prepare("
        UPDATE rct_solicitacoes
        SET status = ?, atualizado_por = ?, atualizado_em = NOW()
        WHERE id = ?
    ");
    $upd->execute([$status, (int)$user['id'], $id]);

    json([
        'ok' => true,
        'solicitacao' => [
            'id' => (int)$id,
            'protocolo' => $row['protocolo'],
            'status' => $status,
        ]
    ]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao atualizar status.', 'debug' => $e->getMessage()], 500);
}