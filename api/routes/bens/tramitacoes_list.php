<?php
require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') { http_response_code(204); exit; }
if ($method !== 'GET') { json(['error' => 'Método não permitido'], 405); }

$bem_id = isset($_GET['bem_id']) ? (int)$_GET['bem_id'] : 0;
if (!$bem_id) { json(['error' => 'Parâmetro bem_id obrigatório.'], 400); }

try {
    $pdo = db();
    $sql = "
        SELECT t.*, 
               f_from.nome AS from_fase_nome,
               f_to.nome   AS to_fase_nome,
               u.nome AS usuario_nome
        FROM tramitacoes_bens t
        LEFT JOIN fases f_from ON f_from.id = t.from_fase_id
        LEFT JOIN fases f_to   ON f_to.id   = t.to_fase_id
        LEFT JOIN usuarios u  ON u.id = t.usuario_operacao_id
        WHERE t.bem_id = ?
        ORDER BY t.created_at DESC
    ";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$bem_id]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    json($rows);
} catch (Throwable $e) {
    error_log('Erro GET /api/bens/tramitacoes: '.$e->getMessage());
    json(['error' => 'Erro ao listar tramitações.'], 500);
}
