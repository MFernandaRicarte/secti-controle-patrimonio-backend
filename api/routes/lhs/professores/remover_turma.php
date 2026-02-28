<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido. Use DELETE.'], 405);
}

requireLhsAdmin();
$pdo = db();

$professorId = $GLOBALS['routeParams']['id'] ?? 0;
$turmaId = $GLOBALS['routeParams']['turma_id'] ?? 0;

if (!$professorId) {
    json(['error' => 'ID do professor não informado.'], 400);
}

if (!$turmaId) {
    json(['error' => 'ID da turma não informado.'], 400);
}

$stmt = $pdo->prepare("
    SELECT pt.id, u.nome AS professor_nome, t.nome AS turma_nome
    FROM lhs_professor_turmas pt
    JOIN usuarios u ON u.id = pt.professor_id
    JOIN lhs_turmas t ON t.id = pt.turma_id
    WHERE pt.professor_id = ? AND pt.turma_id = ?
");
$stmt->execute([$professorId, $turmaId]);
$atribuicao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$atribuicao) {
    json(['error' => 'Atribuição não encontrada.'], 404);
}

$stmtDelete = $pdo->prepare("
    DELETE FROM lhs_professor_turmas WHERE professor_id = ? AND turma_id = ?
");
$stmtDelete->execute([$professorId, $turmaId]);

$stmtOther = $pdo->prepare("
    SELECT professor_id FROM lhs_professor_turmas WHERE turma_id = ? LIMIT 1
");
$stmtOther->execute([$turmaId]);
$outro = $stmtOther->fetch(PDO::FETCH_ASSOC);

if ($outro) {
    $stmtUp = $pdo->prepare("UPDATE lhs_turmas SET professor_id = ? WHERE id = ?");
    $stmtUp->execute([$outro['professor_id'], $turmaId]);
} else {
    $stmtUp = $pdo->prepare("
        UPDATE lhs_turmas SET professor_id = NULL WHERE id = ? AND professor_id = ?
    ");
    $stmtUp->execute([$turmaId, $professorId]);
}

json([
    'message' => 'Turma removida do professor com sucesso.',
    'professor_id' => (int) $professorId,
    'professor_nome' => $atribuicao['professor_nome'],
    'turma_id' => (int) $turmaId,
    'turma_nome' => $atribuicao['turma_nome'],
]);
