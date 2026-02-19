<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

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
            c.id AS curso_id,
            c.nome AS curso_nome,
            c.carga_horaria,
            c.ementa,
            c.descricao,
            c.icone,
            c.nivel,
            c.pre_requisitos,
            c.publico_alvo,
            t.id AS turma_id,
            t.nome AS turma_nome,
            t.horario_inicio,
            t.horario_fim,
            t.data_inicio,
            t.data_fim,
            t.max_vagas,
            t.local_aula,
            t.dias_semana,
            t.status AS turma_status,
            u.nome AS professor_nome,
            (SELECT COUNT(*) FROM lhs_turma_alunos WHERE turma_id = t.id AND status = 'matriculado') AS vagas_ocupadas
        FROM lhs_cursos c
        LEFT JOIN lhs_turmas t ON t.curso_id = c.id AND t.status IN ('aberta', 'em_andamento')
        LEFT JOIN usuarios u ON u.id = t.professor_id
        WHERE c.ativo = 1
        ORDER BY c.nome ASC, t.data_inicio ASC, t.horario_inicio ASC
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
                'turmas' => [],
            ];
        }

        if ($row['turma_id']) {
            $maxVagas = (int) ($row['max_vagas'] ?: 30);
            $vagasOcupadas = (int) $row['vagas_ocupadas'];
            $vagasDisponiveis = max(0, $maxVagas - $vagasOcupadas);

            $cursos[$cursoId]['turmas'][] = [
                'id' => (int) $row['turma_id'],
                'nome' => $row['turma_nome'],
                'professor' => $row['professor_nome'],
                'horario_inicio' => $row['horario_inicio'],
                'horario_fim' => $row['horario_fim'],
                'horario_formatado' => substr($row['horario_inicio'], 0, 5) . ' às ' . substr($row['horario_fim'], 0, 5),
                'data_inicio' => $row['data_inicio'],
                'data_fim' => $row['data_fim'],
                'dias_semana' => $row['dias_semana'],
                'local_aula' => $row['local_aula'],
                'max_vagas' => $maxVagas,
                'vagas_ocupadas' => $vagasOcupadas,
                'vagas_disponiveis' => $vagasDisponiveis,
                'percentual_ocupacao' => $maxVagas > 0 ? round(($vagasOcupadas / $maxVagas) * 100, 1) : 0,
                'tem_vagas' => $vagasDisponiveis > 0,
                'status' => $row['turma_status'],
            ];
        }
    }

    $resultado = array_values(array_map(function ($curso) {
        $curso['total_turmas'] = count($curso['turmas']);
        $curso['aceita_inscricoes'] = count(array_filter($curso['turmas'], fn($t) => $t['tem_vagas'])) > 0;
        $proximaTurma = !empty($curso['turmas']) ? $curso['turmas'][0]['data_inicio'] : null;
        $curso['proxima_turma_inicio'] = $proximaTurma;
        return $curso;
    }, $cursos));

    json($resultado);
} catch (PDOException $e) {
    json(['error' => 'Erro ao buscar cursos disponíveis.'], 500);
    exit;
}
