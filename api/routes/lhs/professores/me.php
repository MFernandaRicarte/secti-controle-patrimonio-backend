<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireAuth();
$pdo = db();

if (!isProfessor($user)) {
    json(['error' => 'Acesso negado. Requer perfil Professor.'], 403);
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
        c.carga_horaria,
        (SELECT COUNT(*) FROM lhs_turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos,
        (SELECT COUNT(*) FROM lhs_aulas a WHERE a.turma_id = t.id) AS total_aulas
    FROM lhs_professor_turmas pt
    JOIN lhs_turmas t ON t.id = pt.turma_id
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    WHERE pt.professor_id = ?
    ORDER BY 
        CASE t.status 
            WHEN 'em_andamento' THEN 1 
            WHEN 'aberta' THEN 2 
            WHEN 'concluida' THEN 3 
            WHEN 'cancelada' THEN 4 
        END,
        t.data_inicio DESC
");
$stmtTurmas->execute([$user['id']]);
$turmas = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

$turmasFormatadas = array_map(function ($t) {
    return [
        'id' => (int) $t['id'],
        'nome' => $t['nome'],
        'curso_nome' => $t['curso_nome'],
        'carga_horaria' => (int) $t['carga_horaria'],
        'status' => $t['status'],
        'horario_inicio' => $t['horario_inicio'],
        'horario_fim' => $t['horario_fim'],
        'data_inicio' => $t['data_inicio'],
        'data_fim' => $t['data_fim'],
        'total_alunos' => (int) $t['total_alunos'],
        'total_aulas' => (int) $t['total_aulas'],
    ];
}, $turmas);

json([
    'professor' => [
        'id' => (int) $user['id'],
        'nome' => $user['nome'],
        'email' => $user['email'],
    ],
    'turmas' => $turmasFormatadas,
    'total_turmas' => count($turmasFormatadas),
]);
