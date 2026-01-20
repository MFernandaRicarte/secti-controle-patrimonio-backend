<?php
require_once __DIR__ . '/../../../config/config.php';
require_once __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido'], 405);
}

$id = $GLOBALS['routeParams']['id'] ?? 0;

if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT 
        a.*,
        t.nome AS turma_nome,
        t.horario_inicio,
        t.horario_fim,
        c.nome AS curso_nome,
        u.nome AS professor_nome
    FROM lhs_aulas a
    INNER JOIN lhs_turmas t ON t.id = a.turma_id
    INNER JOIN lhs_cursos c ON c.id = t.curso_id
    LEFT JOIN usuarios u ON u.id = a.registrado_por
    WHERE a.id = ?
");
$stmt->execute([$id]);
$aula = $stmt->fetch();

if (!$aula) {
    json(['error' => 'Aula não encontrada'], 404);
}

$stmt = $pdo->prepare("
    SELECT 
        p.id,
        p.aluno_id,
        p.presente,
        p.criado_em,
        al.nome AS aluno_nome,
        al.cpf AS aluno_cpf
    FROM lhs_presencas p
    INNER JOIN lhs_alunos al ON al.id = p.aluno_id
    WHERE p.aula_id = ?
    ORDER BY al.nome ASC
");
$stmt->execute([$id]);
$presencas = $stmt->fetchAll();

json([
    'id' => (int)$aula['id'],
    'turma_id' => (int)$aula['turma_id'],
    'turma_nome' => $aula['turma_nome'],
    'curso_nome' => $aula['curso_nome'],
    'horario_inicio' => $aula['horario_inicio'],
    'horario_fim' => $aula['horario_fim'],
    'data_aula' => $aula['data_aula'],
    'conteudo_ministrado' => $aula['conteudo_ministrado'],
    'observacao' => $aula['observacao'],
    'professor_nome' => $aula['professor_nome'],
    'criado_em' => $aula['criado_em'],
    'presencas' => array_map(function ($p) {
        return [
            'id' => (int)$p['id'],
            'aluno_id' => (int)$p['aluno_id'],
            'aluno_nome' => $p['aluno_nome'],
            'aluno_cpf' => $p['aluno_cpf'],
            'presente' => (bool)$p['presente'],
            'criado_em' => $p['criado_em'],
        ];
    }, $presencas),
    'total_presentes' => count(array_filter($presencas, fn($p) => $p['presente'])),
    'total_alunos' => count($presencas),
]);
