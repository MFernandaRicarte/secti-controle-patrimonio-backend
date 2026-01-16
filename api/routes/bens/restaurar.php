<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$id = (int) ($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("SELECT id, excluido_em FROM bens_patrimoniais WHERE id = ?");
    $stmt->execute([$id]);
    $bem = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$bem) {
        json(['error' => 'Bem não encontrado'], 404);
    }

    if (empty($bem['excluido_em'])) {
        json(['error' => 'Este bem não está excluído.'], 409);
    }

    $stmt = $pdo->prepare("
        UPDATE bens_patrimoniais
        SET excluido_em = NULL,
            excluido_por_usuario_id = NULL
        WHERE id = ?
    ");
    $stmt->execute([$id]);

    json(['ok' => true]);
} catch (Throwable $e) {
    error_log("Erro em POST /api/bens/{id}/restaurar: " . $e->getMessage());
    json(['error' => 'Erro ao restaurar bem.', 'details' => $e->getMessage()], 500);
}