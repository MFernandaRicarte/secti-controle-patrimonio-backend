<?php
/**
 * PUT /api/lhs/inscricoes/{id}/rejeitar
 * Rejeita uma inscrição com motivo opcional.
 * Endpoint administrativo.
 */

require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'PUT' && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use PUT ou POST.'], 405);
    exit;
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da inscrição não informado.'], 400);
    exit;
}

$stmt = $pdo->prepare("SELECT * FROM lhs_inscricoes WHERE id = ?");
$stmt->execute([$id]);
$inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscricao) {
    json(['error' => 'Inscrição não encontrada.'], 404);
    exit;
}

if ($inscricao['status'] !== 'pendente') {
    json(['error' => 'Apenas inscrições pendentes podem ser rejeitadas.'], 422);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$motivoRejeicao = trim($input['motivo_rejeicao'] ?? $input['motivo'] ?? '');

$stmtReject = $pdo->prepare("
    UPDATE lhs_inscricoes 
    SET status = 'rejeitado', 
        motivo_rejeicao = ?,
        aprovado_por = ?
    WHERE id = ?
");
$stmtReject->execute([$motivoRejeicao ?: null, $user['id'], $id]);

json([
    'ok' => true,
    'message' => 'Inscrição rejeitada com sucesso.',
]);
