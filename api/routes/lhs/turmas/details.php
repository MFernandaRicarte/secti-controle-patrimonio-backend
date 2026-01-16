<?php

require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['error' => 'Método não permitido. Use GET.']);
    exit;
}

$id = $GLOBALS['routeParams']['id'] ?? 0;
if (!$id) {
    http_response_code(400);
    echo json_encode(['error' => 'ID da turma não informado.']);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Erro ao conectar ao banco.']);
    exit;
}

// Buscar turma com curso e professor
$sql = "
    SELECT t.*, c.nome AS curso_nome, u.nome AS professor_nome
    FROM lhs_turmas t
    LEFT JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = t.professor_id
    WHERE t.id = :id
";
$stmt = $pdo->prepare($sql);
$stmt->execute([':id' => $id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    http_response_code(404);
    echo json_encode(['error' => 'Turma não encontrada.']);
    exit;
}

// Buscar alunos matriculados
$sqlAlunos = "
    SELECT a.id, a.nome, a.cpf, a.email, a.telefone, ta.status, ta.data_matricula
    FROM lhs_turma_alunos ta
    JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = :turma_id
    ORDER BY a.nome ASC
";
$stmtAlunos = $pdo->prepare($sqlAlunos);
$stmtAlunos->execute([':turma_id' => $id]);
$alunos = $stmtAlunos->fetchAll(PDO::FETCH_ASSOC);

$alunosFormatados = array_map(function ($a) {
    return [
        'id' => (int) $a['id'],
        'nome' => $a['nome'],
        'cpf' => $a['cpf'],
        'email' => $a['email'],
        'telefone' => $a['telefone'],
        'status' => $a['status'],
        'data_matricula' => $a['data_matricula'],
    ];
}, $alunos);

$turma = [
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
    'criado_em' => $row['criado_em'],
    'alunos' => $alunosFormatados,
    'total_alunos' => count($alunosFormatados),
];

echo json_encode($turma);
