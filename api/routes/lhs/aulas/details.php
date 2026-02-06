<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    json(['error' => 'ID da aula não informado.'], 400);
}

$stmt = $pdo->prepare("
    SELECT a.*, t.nome AS turma_nome, c.nome AS curso_nome, u.nome AS registrado_por_nome
    FROM lhs_aulas a
    LEFT JOIN lhs_turmas t ON t.id = a.turma_id
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = a.registrado_por
    WHERE a.id = ?
");
$stmt->execute([$id]);
$aula = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$aula) {
    json(['error' => 'Aula não encontrada.'], 404);
}

if (!professorPodeTurma($user, (int)$aula['turma_id'])) {
    json(['error' => 'Acesso negado a esta aula.'], 403);
}

$stmtPresencas = $pdo->prepare("
    SELECT p.*, a.nome AS aluno_nome, a.cpf AS aluno_cpf
    FROM lhs_presencas p
    JOIN lhs_alunos a ON a.id = p.aluno_id
    WHERE p.aula_id = ?
    ORDER BY a.nome ASC
");
$stmtPresencas->execute([$id]);
$presencas = $stmtPresencas->fetchAll(PDO::FETCH_ASSOC);

$presencasFormatadas = array_map(function ($p) {
    return [
        'id' => (int) $p['id'],
        'aluno_id' => (int) $p['aluno_id'],
        'aluno_nome' => $p['aluno_nome'],
        'aluno_cpf' => $p['aluno_cpf'],
        'presente' => (bool) $p['presente'],
    ];
}, $presencas);

$totalPresentes = count(array_filter($presencasFormatadas, fn($p) => $p['presente']));

json([
    'id' => (int) $aula['id'],
    'turma_id' => (int) $aula['turma_id'],
    'turma_nome' => $aula['turma_nome'],
    'curso_nome' => $aula['curso_nome'],
    'data_aula' => $aula['data_aula'],
    'conteudo_ministrado' => $aula['conteudo_ministrado'],
    'observacao' => $aula['observacao'],
    'registrado_por' => $aula['registrado_por'] ? (int) $aula['registrado_por'] : null,
    'registrado_por_nome' => $aula['registrado_por_nome'],
    'criado_em' => $aula['criado_em'],
    'presencas' => $presencasFormatadas,
    'total_presentes' => $totalPresentes,
    'total_alunos' => count($presencasFormatadas),
]);
