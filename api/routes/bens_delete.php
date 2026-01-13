<?php
require __DIR__ . '/../lib/db.php';
require __DIR__ . '/../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Method Not Allowed'], 405);
}

$id = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$body = json_decode(file_get_contents('php://input'), true);
if (!is_array($body)) $body = [];

$usuarioId = 0;
if (isset($body['usuario_id'])) $usuarioId = (int)$body['usuario_id'];
if (!$usuarioId && isset($_GET['usuario_id'])) $usuarioId = (int)$_GET['usuario_id'];

if ($usuarioId <= 0) {
    json(['error' => 'usuario_id é obrigatório para registrar quem excluiu.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare('SELECT id, excluido_em FROM bens_patrimoniais WHERE id = ?');
$stmt->execute([$id]);
$bem = $stmt->fetch();
if (!$bem) {
    json(['error' => 'Bem não encontrado'], 404);
}

if (!empty($bem['excluido_em'])) {
    json(['error' => 'Este bem já está excluído.'], 409);
}

try {
    $stmt = $pdo->prepare("
        UPDATE bens_patrimoniais
        SET excluido_em = NOW(),
            excluido_por_usuario_id = :uid
        WHERE id = :id
    ");
    $stmt->execute([
        ':uid' => $usuarioId,
        ':id'  => $id,
    ]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao excluir (soft delete).', 'details' => $e->getMessage()], 500);
}

json(['ok' => true]);