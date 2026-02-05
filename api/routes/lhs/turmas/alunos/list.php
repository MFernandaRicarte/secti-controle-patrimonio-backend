<?php
require __DIR__ . '/../../../../lib/db.php';
require __DIR__ . '/../../../../lib/http.php';
require __DIR__ . '/../../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$turmaId = $GLOBALS['routeParams']['id'] ?? 0;
if (!$turmaId) {
    json(['error' => 'ID da turma não informado.'], 400);
}

if (!professorPodeTurma($user, $turmaId)) {
    json(['error' => 'Acesso negado a esta turma.'], 403);
}

$stmt = $pdo->prepare("SELECT id FROM lhs_turmas WHERE id = ?");
$stmt->execute([$turmaId]);
if (!$stmt->fetch()) {
    json(['error' => 'Turma não encontrada.'], 404);
}

$sql = "
    SELECT 
        a.id,
        a.nome,
        a.cpf,
        ta.status AS status_matricula
    FROM lhs_turma_alunos ta
    JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = ?
    ORDER BY a.nome ASC
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$turmaId]);
$alunos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$alunosFormatados = array_map(function ($a) {
    return [
        'id' => (int) $a['id'],
        'nome' => $a['nome'],
        'cpf' => $a['cpf'],
        'status_matricula' => $a['status_matricula'],
    ];
}, $alunos);

json($alunosFormatados);
