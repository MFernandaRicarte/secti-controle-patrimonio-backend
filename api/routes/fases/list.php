<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

$pdo = null;
try {
    $pdo = db();
    $stmt = $pdo->prepare("SELECT id, nome, slug, ordem, descricao, ativo, created_at, updated_at FROM fases ORDER BY ordem, nome");
    $stmt->execute();
    $fases = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json($fases);
} catch (Throwable $e) {
    error_log('Erro GET /api/fases: ' . $e->getMessage());
    json(['error' => 'Erro ao obter fases.'], 500);
}
