<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$pdo = db();

$sql = "
    SELECT 
        c.id AS curso_id,
        c.nome AS curso_nome,
        c.carga_horaria,
        c.ementa,
        t.id AS turma_id,
        t.nome AS turma_nome,
        t.horario_inicio,
        t.horario_fim,
        t.data_inicio,
        t.data_fim,
        (SELECT COUNT(*) FROM lhs_turma_alunos WHERE turma_id = t.id) AS vagas_ocupadas
    FROM lhs_cursos c
    LEFT JOIN lhs_turmas t ON t.curso_id = c.id AND t.status = 'aberta'
    WHERE c.ativo = 1
    ORDER BY c.nome ASC, t.horario_inicio ASC
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$cursos = [];
foreach ($rows as $row) {
    $cursoId = (int) $row['curso_id'];
    
    if (!isset($cursos[$cursoId])) {
        $cursos[$cursoId] = [
            'id' => $cursoId,
            'nome' => $row['curso_nome'],
            'carga_horaria' => (int) $row['carga_horaria'],
            'ementa' => $row['ementa'],
            'turmas' => [],
        ];
    }
    
    if ($row['turma_id']) {
        $cursos[$cursoId]['turmas'][] = [
            'id' => (int) $row['turma_id'],
            'nome' => $row['turma_nome'],
            'horario_inicio' => $row['horario_inicio'],
            'horario_fim' => $row['horario_fim'],
            'data_inicio' => $row['data_inicio'],
            'data_fim' => $row['data_fim'],
            'vagas_ocupadas' => (int) $row['vagas_ocupadas'],
        ];
    }
}

json(array_values($cursos));
