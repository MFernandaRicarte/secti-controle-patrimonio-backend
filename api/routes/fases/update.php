<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'PUT') {
    json(['error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($input['id']) ? (int)$input['id'] : 0;

if (!$id) {
    json(['error' => 'ID inválido.'], 400);
}

$nome = isset($input['nome']) ? trim($input['nome']) : '';
$slug = isset($input['slug']) ? trim($input['slug']) : null;
$ordem = isset($input['ordem']) ? (int)$input['ordem'] : 0;
$descricao = isset($input['descricao']) ? $input['descricao'] : null;
$ativo = isset($input['ativo']) ? (int)$input['ativo'] : 1;

$pdo = null;
try {
    $pdo = db();
    $stmt = $pdo->prepare("UPDATE fases SET nome = ?, slug = ?, ordem = ?, descricao = ?, ativo = ? WHERE id = ?");
    $stmt->execute([$nome, $slug, $ordem, $descricao, $ativo, $id]);

    $stmt = $pdo->prepare("SELECT id, nome, slug, ordem, descricao, ativo, created_at, updated_at FROM fases WHERE id = ?");
    $stmt->execute([$id]);
    $fase = $stmt->fetch(PDO::FETCH_ASSOC);

    json($fase);
} catch (Throwable $e) {
    error_log('Erro PUT /api/fases: ' . $e->getMessage());
    json(['error' => 'Erro ao atualizar fase.'], 500);
}
