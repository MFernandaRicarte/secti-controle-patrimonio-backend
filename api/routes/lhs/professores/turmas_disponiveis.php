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

$professorId = isset($_GET['professor_id']) ? (int) $_GET['professor_id'] : 0;

$where = '';
$params = [];

if ($professorId > 0) {
    $where = " AND t.id NOT IN (SELECT turma_id FROM lhs_professor_turmas WHERE professor_id = :pid)";
    $params[':pid'] = $professorId;
}

$sql = "
    SELECT 
        t.id,
        t.nome,
        t.status,
        t.horario_inicio,
        t.horario_fim,
        t.data_inicio,
        t.data_fim,
        c.nome AS curso_nome,
        u.nome AS professor_atual,
        (SELECT COUNT(*) FROM lhs_turma_alunos ta WHERE ta.turma_id = t.id) AS total_alunos
    FROM lhs_turmas t
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = t.professor_id
    WHERE t.status IN ('aberta', 'em_andamento') {$where}
    ORDER BY t.data_inicio DESC
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$turmas = $stmt->fetchAll(PDO::FETCH_ASSOC);

$resultado = array_map(function ($t) {
    return [
        'id' => (int) $t['id'],
        'nome' => $t['nome'],
        'curso_nome' => $t['curso_nome'],
        'status' => $t['status'],
        'horario_inicio' => $t['horario_inicio'],
        'horario_fim' => $t['horario_fim'],
        'data_inicio' => $t['data_inicio'],
        'data_fim' => $t['data_fim'],
        'professor_atual' => $t['professor_atual'],
        'total_alunos' => (int) $t['total_alunos'],
    ];
}, $turmas);

json($resultado);
