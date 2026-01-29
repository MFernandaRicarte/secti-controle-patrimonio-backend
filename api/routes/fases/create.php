<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($method !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];
// fallback: accept form-encoded bodies (for debugging/clients that don't send raw JSON)
if (empty($input) && !empty($_POST)) {
    $input = $_POST;
}

$nome = isset($input['nome']) ? trim($input['nome']) : '';
$slug = isset($input['slug']) ? trim($input['slug']) : null;
$ordem = isset($input['ordem']) ? (int)$input['ordem'] : 0;
$descricao = isset($input['descricao']) ? $input['descricao'] : null;

if ($nome === '') {
    json(['error' => 'Nome é obrigatório.'], 400);
}

$pdo = null;
try {
    $pdo = db();
    $stmt = $pdo->prepare("INSERT INTO fases (nome, slug, ordem, descricao) VALUES (?, ?, ?, ?)");
    $stmt->execute([$nome, $slug, $ordem, $descricao]);

    $id = (int)$pdo->lastInsertId();

    $stmt = $pdo->prepare("SELECT id, nome, slug, ordem, descricao, ativo, created_at, updated_at FROM fases WHERE id = ?");
    $stmt->execute([$id]);
    $fase = $stmt->fetch(PDO::FETCH_ASSOC);

    json($fase, 201);
} catch (Throwable $e) {
    error_log('Erro POST /api/fases: ' . $e->getMessage());
    json(['error' => 'Erro ao criar fase.'], 500);
}
