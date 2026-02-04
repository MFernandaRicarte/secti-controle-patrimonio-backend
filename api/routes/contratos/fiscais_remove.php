<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido. Use POST.'], 405);
    exit;
}

$id = isset($GLOBALS['routeParams']['id']) ? (int) $GLOBALS['routeParams']['id'] : 0;

if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$stmt = $pdo->prepare('SELECT id FROM contratos WHERE id = ?');
$stmt->execute([$id]);
if (!$stmt->fetch()) {
    json(['error' => 'Contrato não encontrado'], 404);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$usuarioId = isset($input['usuario_id']) ? (int) $input['usuario_id'] : (int) $usuario['id'];
$dataFim = trim($input['data_fim'] ?? '');

if ($dataFim === '') {
    json(['error' => 'data_fim é obrigatória'], 400);
    exit;
}

if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dataFim)) {
    json(['error' => 'data_fim deve estar no formato YYYY-MM-DD'], 400);
    exit;
}

try {
    $stmt = $pdo->prepare("
        UPDATE contratos_fiscais
        SET ativo = FALSE, data_termino = ?
        WHERE contrato_id = ? AND usuario_id = ? AND ativo = TRUE
    ");
    $stmt->execute([$dataFim, $id, $usuarioId]);

    json(['message' => 'Fiscal desvinculado com sucesso']);
} catch (PDOException $e) {
    error_log('Erro ao desmarcar fiscal: ' . $e->getMessage());
    json(['error' => 'Erro ao desvincularf fiscal'], 500);
}
