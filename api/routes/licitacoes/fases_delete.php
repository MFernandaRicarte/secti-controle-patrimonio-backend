<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];
if ($method === 'OPTIONS') { http_response_code(204); exit; }
if ($method !== 'DELETE') { json(['error' => 'Método não permitido'], 405); }

$input = json_decode(file_get_contents('php://input'), true) ?? [];
if (empty($input) && !empty($_POST)) { $input = $_POST; }

$id = isset($input['id']) ? (int)$input['id'] : 0;
if (!$id) { json(['error' => 'ID inválido.'], 400); }

try {
    $pdo = db();
    $stmt = $pdo->prepare("DELETE FROM licitacoes_fases WHERE id = ?");
    $stmt->execute([$id]);
    json(['ok' => true]);
} catch (Throwable $e) {
    error_log('Erro DELETE /api/licitacoes/fases: '.$e->getMessage());
    json(['error' => 'Erro ao remover fase.'], 500);
}
