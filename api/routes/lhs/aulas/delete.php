<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'DELETE') {
    json(['error' => 'Método não permitido. Use DELETE.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da aula não informado.'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM lhs_aulas WHERE id = ?");
$stmt->execute([$id]);
$aula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aula) {
    json(['error' => 'Aula não encontrada.'], 404);
}

if (!professorPodeTurma($user, (int)$aula['turma_id'])) {
    json(['error' => 'Acesso negado a esta aula.'], 403);
}

$stmtDelete = $pdo->prepare("DELETE FROM lhs_aulas WHERE id = ?");
$stmtDelete->execute([$id]);

json([
    'ok' => true,
    'message' => 'Aula excluída com sucesso.',
]);
