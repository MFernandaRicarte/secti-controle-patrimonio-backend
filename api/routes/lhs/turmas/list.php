<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireProfessorOrAdmin();
$pdo = db();

$cursoId = isset($_GET['curso_id']) ? (int) $_GET['curso_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';

$where = '';
$params = [];

if (isProfessor($user)) {
    $where .= " AND t.professor_id = :professor_id";
    $params[':professor_id'] = $user['id'];
}

if ($cursoId > 0) {
    $where .= " AND t.curso_id = :curso_id";
    $params[':curso_id'] = $cursoId;
}

if ($status !== '') {
    $where .= " AND t.status = :status";
    $params[':status'] = $status;
}

$sql = "
    SELECT
        t.id,
        t.curso_id,
        c.nome AS curso_nome,
        t.professor_id,
        u.nome AS professor_nome,
        t.nome,
        t.horario_inicio,
        t.horario_fim,
        t.data_inicio,
        t.data_fim,
        t.status,
        t.criado_em,
        (SELECT COUNT(*) FROM lhs_turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos
    FROM lhs_turmas t
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = t.professor_id
    WHERE 1=1 {$where}
    ORDER BY t.data_inicio DESC, t.criado_em DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$turmas = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'curso_id' => (int) $row['curso_id'],
        'curso_nome' => $row['curso_nome'],
        'professor_id' => $row['professor_id'] ? (int) $row['professor_id'] : null,
        'professor_nome' => $row['professor_nome'],
        'nome' => $row['nome'],
        'horario_inicio' => $row['horario_inicio'],
        'horario_fim' => $row['horario_fim'],
        'data_inicio' => $row['data_inicio'],
        'data_fim' => $row['data_fim'],
        'status' => $row['status'],
        'total_alunos' => (int) $row['total_alunos'],
        'criado_em' => $row['criado_em'],
    ];
}, $rows);

json($turmas);
