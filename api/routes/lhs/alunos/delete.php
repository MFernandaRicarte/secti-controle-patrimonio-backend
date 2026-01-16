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
    json(['error' => 'ID do aluno não informado.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

// Verificar se aluno existe
$stmt = $pdo->prepare("SELECT id, nome FROM lhs_alunos WHERE id = :id");
$stmt->execute([':id' => $id]);
$aluno = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aluno) {
    json(['error' => 'Aluno não encontrado.'], 404);
    exit;
}

// Verificar se aluno está matriculado em turmas
$stmt2 = $pdo->prepare("SELECT COUNT(*) as total FROM lhs_turma_alunos WHERE aluno_id = :id");
$stmt2->execute([':id' => $id]);
$count = $stmt2->fetch(PDO::FETCH_ASSOC);

if ($count && (int) $count['total'] > 0) {
    json([
        'error' => 'Não é possível excluir este aluno.',
        'detalhes' => 'O aluno está matriculado em ' . $count['total'] . ' turma(s). Remova as matrículas antes de excluir.'
    ], 409);
    exit;
}

try {
    $stmt = $pdo->prepare("DELETE FROM lhs_alunos WHERE id = :id");
    $stmt->execute([':id' => $id]);

    json(['message' => 'Aluno excluído com sucesso.', 'id' => (int) $id]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao excluir aluno.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
