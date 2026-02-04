<?php

require __DIR__ . '/../../../../lib/http.php';
require __DIR__ . '/../../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido. Use DELETE.'], 405);
    exit;
}

$turmaId = $GLOBALS['routeParams']['id'] ?? 0;
$alunoId = $GLOBALS['routeParams']['aluno_id'] ?? 0;

if (!$turmaId) {
    json(['error' => 'ID da turma não informado.'], 400);
    exit;
}

if (!$alunoId) {
    json(['error' => 'ID do aluno não informado.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

// Verificar se matrícula existe
$stmt = $pdo->prepare("
    SELECT ta.id, a.nome AS aluno_nome
    FROM lhs_turma_alunos ta
    JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = :turma_id AND ta.aluno_id = :aluno_id
");
$stmt->execute([':turma_id' => $turmaId, ':aluno_id' => $alunoId]);
$matricula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$matricula) {
    json(['error' => 'Matrícula não encontrada.'], 404);
    exit;
}

try {
    $stmtDelete = $pdo->prepare("
        DELETE FROM lhs_turma_alunos WHERE turma_id = :turma_id AND aluno_id = :aluno_id
    ");
    $stmtDelete->execute([':turma_id' => $turmaId, ':aluno_id' => $alunoId]);

    json([
        'message' => 'Aluno removido da turma com sucesso.',
        'aluno_id' => (int) $alunoId,
        'aluno_nome' => $matricula['aluno_nome'],
    ]);
} catch (PDOException $e) {
    json(['error' => 'Erro ao remover aluno da turma.', 'detalhes' => $e->getMessage()], 500);
    exit;
}
