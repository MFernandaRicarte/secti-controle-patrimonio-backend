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
$fase = isset($input['fase']) ? trim($input['fase']) : '';
$data_inicio = isset($input['data_inicio']) ? $input['data_inicio'] : null;
$data_fim = isset($input['data_fim']) ? $input['data_fim'] : null;
$prazo_dias = isset($input['prazo_dias']) && $input['prazo_dias'] !== '' ? (int)$input['prazo_dias'] : null;
$responsavel_id = isset($input['responsavel_id']) && $input['responsavel_id'] !== '' ? (int)$input['responsavel_id'] : null;
$observacoes = isset($input['observacoes']) ? $input['observacoes'] : null;

if (!$licitacao_id || $fase === '' || !$data_inicio) {
    json(['error' => 'licitacao_id, fase e data_inicio são obrigatórios.'], 400);
}

try {
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO licitacoes_fases (licitacao_id, fase, data_inicio, data_fim, prazo_dias, responsavel_id, observacoes) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$licitacao_id, $fase, $data_inicio, $data_fim, $prazo_dias, $responsavel_id, $observacoes]);
    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id, licitacao_id, fase, data_inicio, data_fim, prazo_dias, responsavel_id, observacoes, criado_em FROM licitacoes_fases WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    json($row, 201);
} catch (Throwable $e) {
    error_log('Erro POST /api/licitacoes/fases: '.$e->getMessage());
    json(['error' => 'Erro ao criar fase da licitação.'], 500);
}
