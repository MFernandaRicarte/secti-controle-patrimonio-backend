<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

try {
    $sql = "
        SELECT 
            c.id, c.nome, c.carga_horaria, c.ementa, c.descricao,
            c.icone, c.nivel, c.pre_requisitos, c.publico_alvo,
            (SELECT COUNT(*) FROM lhs_turmas t WHERE t.curso_id = c.id AND t.status IN ('aberta', 'em_andamento')) AS turmas_abertas,
            (SELECT MIN(t2.data_inicio) FROM lhs_turmas t2 WHERE t2.curso_id = c.id AND t2.status = 'aberta' AND t2.data_inicio >= CURDATE()) AS proxima_turma,
            (SELECT COUNT(DISTINCT ta.aluno_id) FROM lhs_turma_alunos ta JOIN lhs_turmas t3 ON t3.id = ta.turma_id WHERE t3.curso_id = c.id AND ta.status = 'aprovado') AS alunos_formados
        FROM lhs_cursos c
        WHERE c.ativo = 1
        ORDER BY c.nome ASC
    ";

    $stmt = $pdo->query($sql);
    $cursos = [];

    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $cursos[] = [
            'id' => (int) $row['id'],
            'nome' => $row['nome'],
            'carga_horaria' => (int) $row['carga_horaria'],
            'carga_horaria_formatada' => (int) $row['carga_horaria'] . 'h',
            'ementa' => $row['ementa'],
            'descricao' => $row['descricao'],
            'icone' => $row['icone'],
            'nivel' => $row['nivel'],
            'nivel_texto' => match($row['nivel']) {
                'iniciante' => 'Iniciante',
                'intermediario' => 'Intermediário',
                'avancado' => 'Avançado',
                default => $row['nivel'],
            },
            'pre_requisitos' => $row['pre_requisitos'],
            'publico_alvo' => $row['publico_alvo'],
            'turmas_abertas' => (int) $row['turmas_abertas'],
            'proxima_turma' => $row['proxima_turma'],
            'alunos_formados' => (int) $row['alunos_formados'],
            'aceita_inscricoes' => (int) $row['turmas_abertas'] > 0,
        ];
    }

    json($cursos);
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar cursos.'], 500);
    exit;
}
