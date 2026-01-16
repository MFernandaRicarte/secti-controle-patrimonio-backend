<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido. Use DELETE.'], 405);
    exit;
}

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da turma não informado.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

// Verificar se turma existe
$stmt = $pdo->prepare("SELECT id, nome FROM lhs_turmas WHERE id = :id");
$stmt->execute([':id' => $id]);
$turma = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$turma) {
    json(['error' => 'Turma não encontrada.'], 404);
    exit;
}

try {
    // As matrículas serão deletadas em cascata (ON DELETE CASCADE)
    $stmt = $pdo->prepare("DELETE FROM lhs_turmas WHERE id = :id");
    $stmt->execute([':id' => $id]);

    json(['message' => 'Turma excluída com sucesso.', 'id' => (int) $id]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao excluir turma.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
