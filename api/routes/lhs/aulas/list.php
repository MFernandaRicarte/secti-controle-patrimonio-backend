<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

$turmaId = $_GET['turma_id'] ?? null;

$pdo = db();

$sql = "
    SELECT 
        a.id,
        a.turma_id,
        a.data_aula,
        a.conteudo_ministrado,
        a.observacao,
        a.registrado_por,
        a.criado_em,
        t.nome AS turma_nome,
        c.nome AS curso_nome,
        u.nome AS professor_nome,
        (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id AND presente = TRUE) AS total_presentes,
        (SELECT COUNT(*) FROM lhs_presencas WHERE aula_id = a.id) AS total_alunos
    FROM lhs_aulas a
    INNER JOIN lhs_turmas t ON t.id = a.turma_id
    INNER JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = a.registrado_por
";

$params = [];

if ($turmaId) {
    $sql .= " WHERE a.turma_id = ?";
    $params[] = $turmaId;
}

$sql .= " ORDER BY a.data_aula DESC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll();

$aulas = array_map(function ($row) {
    return [
        'id' => (int)$row['id'],
        'turma_id' => (int)$row['turma_id'],
        'turma_nome' => $row['turma_nome'],
        'curso_nome' => $row['curso_nome'],
        'data_aula' => $row['data_aula'],
        'conteudo_ministrado' => $row['conteudo_ministrado'],
        'observacao' => $row['observacao'],
        'professor_nome' => $row['professor_nome'],
        'total_presentes' => (int)$row['total_presentes'],
        'total_alunos' => (int)$row['total_alunos'],
        'criado_em' => $row['criado_em'],
    ];
}, $rows);

json($aulas);
