<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(204); exit; }
if ($method !== 'POST') { json(['error' => 'Método não permitido'], 405); }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($input) && !empty($_POST)) { $input = $_POST; }

$licitacao_id = isset($input['licitacao_id']) ? (int)$input['licitacao_id'] : 0;
$to_fase_id = isset($input['to_fase_id']) ? (int)$input['to_fase_id'] : 0;
$usuario_operacao_id = isset($input['usuario_operacao_id']) ? (int)$input['usuario_operacao_id'] : null;
$comentario = isset($input['comentario']) ? $input['comentario'] : null;

if (!$licitacao_id || !$to_fase_id) {
    json(['error' => 'licitacao_id e to_fase_id são obrigatórios.'], 400);
}

$pdo = null;
try {
    $pdo = db();
    $pdo->beginTransaction();

    $stmt = $pdo->prepare("SELECT id, current_fase_id FROM licitacoes WHERE id = ? FOR UPDATE");
    $stmt->execute([$licitacao_id]);
    $licit = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$licit) { $pdo->rollBack(); json(['error' => 'Licitação não encontrada.'], 404); }

    $from_fase_id = $licit['current_fase_id'] ?? null;

    $stmt = $pdo->prepare("INSERT INTO tramitacoes_licitacoes (licitacao_id, from_fase_id, to_fase_id, usuario_operacao_id, comentario) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$licitacao_id, $from_fase_id, $to_fase_id, $usuario_operacao_id, $comentario]);
    $tram_id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("UPDATE licitacoes SET current_fase_id = ? WHERE id = ?");
    $stmt->execute([$to_fase_id, $licitacao_id]);

    $stmt = $pdo->prepare("SELECT t.*, f_from.nome AS from_fase_nome, f_to.nome AS to_fase_nome, u.nome AS usuario_nome FROM tramitacoes_licitacoes t LEFT JOIN fases f_from ON f_from.id = t.from_fase_id LEFT JOIN fases f_to ON f_to.id = t.to_fase_id LEFT JOIN usuarios u ON u.id = t.usuario_operacao_id WHERE t.id = ?");
    $stmt->execute([$tram_id]);
    $tram = $stmt->fetch(PDO::FETCH_ASSOC);

    $pdo->commit();
    json($tram, 201);
} catch (Throwable $e) {
    if ($pdo && $pdo->inTransaction()) { $pdo->rollBack(); }
    error_log('Erro POST /api/licitacoes/tramitacoes: '.$e->getMessage());
    json(['error' => 'Erro ao criar tramitação.'], 500);
}
