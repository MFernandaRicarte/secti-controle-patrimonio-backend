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

$turmaId = isset($_GET['turma_id']) ? (int) $_GET['turma_id'] : 0;

$where = '';
$params = [];

if ($turmaId > 0) {
    if (!professorPodeTurma($user, $turmaId)) {
        json(['error' => 'Acesso negado a esta turma.'], 403);
    }
    $where .= " AND a.turma_id = :turma_id";
    $params[':turma_id'] = $turmaId;
} elseif (isProfessor($user)) {
    $turmasProfessor = getTurmasProfessor($user['id']);
    if (empty($turmasProfessor)) {
        json([]);
    }
    $placeholders = implode(',', array_fill(0, count($turmasProfessor), '?'));
    $where .= " AND a.turma_id IN ($placeholders)";
    $params = array_values($turmasProfessor);
}

$sql = "
    SELECT
        a.id,
        a.turma_id,
        t.nome AS turma_nome,
        c.nome AS curso_nome,
        a.data_aula,
        a.conteudo_ministrado,
        a.observacao,
        a.registrado_por,
        u.nome AS registrado_por_nome,
        a.criado_em,
        (SELECT COUNT(*) FROM lhs_presencas p WHERE p.aula_id = a.id AND p.presente = 1) AS total_presentes,
        (SELECT COUNT(*) FROM lhs_presencas p WHERE p.aula_id = a.id) AS total_alunos
    FROM lhs_aulas a
    LEFT JOIN lhs_turmas t ON t.id = a.turma_id
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = a.registrado_por
    WHERE 1=1 {$where}
    ORDER BY a.data_aula DESC, a.criado_em DESC
    LIMIT 500
";

$stmt = $pdo->prepare($sql);

if (strpos($where, 'IN') !== false) {
    $stmt->execute($params);
} else {
    $stmt->execute($params);
}

$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$aulas = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'turma_id' => (int) $row['turma_id'],
        'turma_nome' => $row['turma_nome'],
        'curso_nome' => $row['curso_nome'],
        'data_aula' => $row['data_aula'],
        'conteudo_ministrado' => $row['conteudo_ministrado'],
        'observacao' => $row['observacao'],
        'registrado_por' => $row['registrado_por'] ? (int) $row['registrado_por'] : null,
        'registrado_por_nome' => $row['registrado_por_nome'],
        'total_presentes' => (int) $row['total_presentes'],
        'total_alunos' => (int) $row['total_alunos'],
        'criado_em' => $row['criado_em'],
    ];
}, $rows);

json($aulas);
