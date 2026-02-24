<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireLhsAdmin();
$pdo = db();

$turmaId = isset($_GET['turma_id']) ? (int) $_GET['turma_id'] : 0;
$alunoId = isset($_GET['aluno_id']) ? (int) $_GET['aluno_id'] : 0;

$where = '';
$params = [];

if ($turmaId > 0) {
    $where .= " AND cert.turma_id = :turma_id";
    $params[':turma_id'] = $turmaId;
}

if ($alunoId > 0) {
    $where .= " AND cert.aluno_id = :aluno_id";
    $params[':aluno_id'] = $alunoId;
}

$sql = "
    SELECT 
        cert.*,
        a.nome AS aluno_nome,
        a.cpf AS aluno_cpf,
        t.nome AS turma_nome,
        c.nome AS curso_nome,
        c.carga_horaria,
        u.nome AS emitido_por_nome
    FROM lhs_certificados cert
    JOIN lhs_alunos a ON a.id = cert.aluno_id
    JOIN lhs_turmas t ON t.id = cert.turma_id
    JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = cert.emitido_por
    WHERE 1=1 {$where}
    ORDER BY cert.emitido_em DESC
    LIMIT 200
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$certificados = array_map(function ($row) {
    return [
        'id' => (int) $row['id'],
        'codigo_validacao' => $row['codigo_validacao'],
        'aluno_id' => (int) $row['aluno_id'],
        'aluno_nome' => $row['aluno_nome'],
        'aluno_cpf' => $row['aluno_cpf'],
        'turma_id' => (int) $row['turma_id'],
        'turma_nome' => $row['turma_nome'],
        'curso_nome' => $row['curso_nome'],
        'carga_horaria' => (int) $row['carga_horaria'],
        'frequencia_final' => (float) $row['frequencia_final'],
        'emitido_em' => $row['emitido_em'],
        'emitido_por' => $row['emitido_por'] ? (int) $row['emitido_por'] : null,
        'emitido_por_nome' => $row['emitido_por_nome'],
    ];
}, $rows);

json($certificados);
