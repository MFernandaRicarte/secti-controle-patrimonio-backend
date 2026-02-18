<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

$id = $GLOBALS['routeParams']['id'] ?? null;
$id = (int) $id;

if ($id <= 0) {
    json(['error' => 'ID do curso inválido.'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT c.*,
            (SELECT COUNT(DISTINCT ta.aluno_id) 
             FROM lhs_turma_alunos ta 
             JOIN lhs_turmas t ON t.id = ta.turma_id 
             WHERE t.curso_id = c.id AND ta.status = 'aprovado') AS alunos_formados,
            (SELECT COUNT(DISTINCT ta2.aluno_id) 
             FROM lhs_turma_alunos ta2 
             JOIN lhs_turmas t2 ON t2.id = ta2.turma_id 
             WHERE t2.curso_id = c.id AND ta2.status = 'matriculado') AS alunos_cursando
        FROM lhs_cursos c
        WHERE c.id = ? AND c.ativo = 1
    ");
    $stmt->execute([$id]);
    $curso = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$curso) {
        json(['error' => 'Curso não encontrado ou não está disponível.'], 404);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            t.id, t.nome, t.horario_inicio, t.horario_fim,
            t.data_inicio, t.data_fim, t.max_vagas, t.local_aula,
            t.dias_semana, t.status,
            u.nome AS professor_nome,
            (SELECT COUNT(*) FROM lhs_turma_alunos WHERE turma_id = t.id AND status = 'matriculado') AS vagas_ocupadas
        FROM lhs_turmas t
        LEFT JOIN usuarios u ON u.id = t.professor_id
        WHERE t.curso_id = ? AND t.status IN ('aberta', 'em_andamento')
        ORDER BY t.data_inicio ASC
    ");
    $stmt->execute([$id]);
    $turmasRows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $turmas = array_map(function ($t) {
        $maxVagas = (int) ($t['max_vagas'] ?: 30);
        $vagasOcupadas = (int) $t['vagas_ocupadas'];
        $vagasDisponiveis = max(0, $maxVagas - $vagasOcupadas);

        return [
            'id' => (int) $t['id'],
            'nome' => $t['nome'],
            'professor' => $t['professor_nome'],
            'horario_inicio' => $t['horario_inicio'],
            'horario_fim' => $t['horario_fim'],
            'horario_formatado' => substr($t['horario_inicio'], 0, 5) . ' às ' . substr($t['horario_fim'], 0, 5),
            'data_inicio' => $t['data_inicio'],
            'data_fim' => $t['data_fim'],
            'dias_semana' => $t['dias_semana'],
            'local_aula' => $t['local_aula'],
            'max_vagas' => $maxVagas,
            'vagas_ocupadas' => $vagasOcupadas,
            'vagas_disponiveis' => $vagasDisponiveis,
            'percentual_ocupacao' => $maxVagas > 0 ? round(($vagasOcupadas / $maxVagas) * 100, 1) : 0,
            'tem_vagas' => $vagasDisponiveis > 0,
            'status' => $t['status'],
        ];
    }, $turmasRows);

    $stmtDepoimentos = $pdo->prepare("
        SELECT nome, texto, nota 
        FROM lhs_depoimentos 
        WHERE curso_nome = ? AND aprovado = 1 
        ORDER BY nota DESC 
        LIMIT 3
    ");
    $stmtDepoimentos->execute([$curso['nome']]);
    $depoimentos = $stmtDepoimentos->fetchAll(PDO::FETCH_ASSOC);

    $response = [
        'id' => (int) $curso['id'],
        'nome' => $curso['nome'],
        'carga_horaria' => (int) $curso['carga_horaria'],
        'carga_horaria_formatada' => (int) $curso['carga_horaria'] . 'h',
        'ementa' => $curso['ementa'],
        'descricao' => $curso['descricao'],
        'icone' => $curso['icone'],
        'nivel' => $curso['nivel'],
        'nivel_texto' => match($curso['nivel']) {
            'iniciante' => 'Iniciante',
            'intermediario' => 'Intermediário',
            'avancado' => 'Avançado',
            default => $curso['nivel'],
        },
        'pre_requisitos' => $curso['pre_requisitos'],
        'publico_alvo' => $curso['publico_alvo'],
        'alunos_formados' => (int) $curso['alunos_formados'],
        'alunos_cursando' => (int) $curso['alunos_cursando'],
        'turmas' => $turmas,
        'total_turmas_abertas' => count($turmas),
        'aceita_inscricoes' => count(array_filter($turmas, fn($t) => $t['tem_vagas'])) > 0,
        'depoimentos' => array_map(function ($d) {
            return [
                'nome' => $d['nome'],
                'texto' => $d['texto'],
                'nota' => (int) $d['nota'],
            ];
        }, $depoimentos),
    ];

    json($response);
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar detalhes do curso.'], 500);
    exit;
}
