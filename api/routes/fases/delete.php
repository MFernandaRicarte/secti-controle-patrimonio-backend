<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'DELETE') {
    json(['error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
$id = isset($input['id']) ? (int)$input['id'] : 0;

if (!$id) {
    json(['error' => 'ID inválido.'], 400);
}

$pdo = null;
try {
    $pdo = db();
    // soft delete
    $stmt = $pdo->prepare("UPDATE fases SET ativo = 0 WHERE id = ?");
    $stmt->execute([$id]);

    json(['ok' => true]);
} catch (Throwable $e) {
    error_log('Erro DELETE /api/fases: ' . $e->getMessage());
    json(['error' => 'Erro ao remover fase.'], 500);
}
