<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

requireLhsAdmin();
$pdo = db();

$professorId = $GLOBALS['routeParams']['id'] ?? 0;
if (!$professorId) {
    json(['error' => 'ID do professor não informado.'], 400);
}

$stmt = $pdo->prepare("
    SELECT u.id, u.nome, u.email, u.matricula, p.nome AS perfil_nome
    FROM usuarios u
    JOIN perfis p ON p.id = u.perfil_id
    WHERE u.id = ? AND UPPER(p.nome) = 'PROFESSOR'
");
$stmt->execute([$professorId]);
$professor = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$professor) {
    json(['error' => 'Professor não encontrado.'], 404);
}

$stmtTurmas = $pdo->prepare("
    SELECT 
        t.id,
        t.nome,
        t.status,
        t.horario_inicio,
        t.horario_fim,
        t.data_inicio,
        t.data_fim,
        c.nome AS curso_nome,
        pt.atribuido_em,
        ua.nome AS atribuido_por_nome,
        (SELECT COUNT(*) FROM lhs_turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos
    FROM lhs_professor_turmas pt
    JOIN lhs_turmas t ON t.id = pt.turma_id
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios ua ON ua.id = pt.atribuido_por
    WHERE pt.professor_id = ?
    ORDER BY t.data_inicio DESC
");
$stmtTurmas->execute([$professorId]);
$turmas = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

$turmasFormatadas = array_map(function ($t) {
    return [
        'id' => (int) $t['id'],
        'nome' => $t['nome'],
        'curso_nome' => $t['curso_nome'],
        'status' => $t['status'],
        'horario_inicio' => $t['horario_inicio'],
        'horario_fim' => $t['horario_fim'],
        'data_inicio' => $t['data_inicio'],
        'data_fim' => $t['data_fim'],
        'total_alunos' => (int) $t['total_alunos'],
        'atribuido_em' => $t['atribuido_em'],
        'atribuido_por_nome' => $t['atribuido_por_nome'],
    ];
}, $turmas);

json([
    'id' => (int) $professor['id'],
    'nome' => $professor['nome'],
    'email' => $professor['email'],
    'matricula' => $professor['matricula'],
    'turmas' => $turmasFormatadas,
    'total_turmas' => count($turmasFormatadas),
]);
