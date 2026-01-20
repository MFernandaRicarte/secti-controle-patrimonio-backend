<?php
require_once __DIR__ . '/../../../../config/config.php';
require_once __DIR__ . '/../../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

$turmaId = $GLOBALS['routeParams']['id'] ?? 0;

if ($turmaId <= 0) {
    json(['error' => 'ID da turma inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("SELECT id FROM lhs_turmas WHERE id = ?");
$stmt->execute([$turmaId]);
if (!$stmt->fetch()) {
    json(['error' => 'Turma não encontrada'], 404);
}

$stmt = $pdo->prepare("
    SELECT 
        ta.id AS matricula_id,
        ta.status AS status_matricula,
        ta.data_matricula,
        a.id AS aluno_id,
        a.nome,
        a.cpf,
        a.telefone,
        a.email
    FROM lhs_turma_alunos ta
    INNER JOIN lhs_alunos a ON a.id = ta.aluno_id
    WHERE ta.turma_id = ? AND ta.status = 'matriculado'
    ORDER BY a.nome ASC
");
$stmt->execute([$turmaId]);
$alunos = $stmt->fetchAll();

json(array_map(function ($row) {
    return [
        'matricula_id' => (int)$row['matricula_id'],
        'aluno_id' => (int)$row['aluno_id'],
        'nome' => $row['nome'],
        'cpf' => $row['cpf'],
        'telefone' => $row['telefone'],
        'email' => $row['email'],
        'status_matricula' => $row['status_matricula'],
        'data_matricula' => $row['data_matricula'],
    ];
}, $alunos));
