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
    $programa = [
        'nome' => 'Lan House Social',
        'subtitulo' => 'Inclusão digital para transformar vidas',
        'descricao' => 'O Lan House Social é um programa da Secretaria de Ciência, Tecnologia e Inovação (SECTI) que oferece cursos gratuitos de informática e tecnologia para a comunidade. Nosso objetivo é promover a inclusão digital, capacitar cidadãos e abrir portas para novas oportunidades.',
        'destaques' => [
            ['icone' => 'GraduationCap', 'titulo' => 'Cursos Gratuitos', 'descricao' => 'Todos os cursos são 100% gratuitos'],
            ['icone' => 'Monitor', 'titulo' => 'Infraestrutura Completa', 'descricao' => 'Computadores individuais para cada aluno'],
            ['icone' => 'Award', 'titulo' => 'Certificação', 'descricao' => 'Certificado digital ao concluir o curso'],
            ['icone' => 'Users', 'titulo' => 'Professores Qualificados', 'descricao' => 'Acompanhamento personalizado'],
        ],
    ];

    $stmtCursos = $pdo->query("
        SELECT 
            c.id,
            c.nome,
            c.carga_horaria,
            c.ementa,
            c.descricao,
            c.icone,
            c.nivel,
            c.pre_requisitos,
            c.publico_alvo
        FROM lhs_cursos c
        WHERE c.ativo = 1
        ORDER BY c.nome ASC
    ");
    $cursosRows = $stmtCursos->fetchAll(PDO::FETCH_ASSOC);

    $cursos = [];
    foreach ($cursosRows as $curso) {
        $cursoId = (int) $curso['id'];

        $stmtTurmas = $pdo->prepare("
            SELECT 
                t.id,
                t.nome,
                t.horario_inicio,
                t.horario_fim,
                t.data_inicio,
                t.data_fim,
                t.max_vagas,
                t.local_aula,
                t.dias_semana,
                t.status,
                u.nome AS professor_nome,
                (SELECT COUNT(*) FROM lhs_turma_alunos WHERE turma_id = t.id AND status = 'matriculado') AS vagas_ocupadas
            FROM lhs_turmas t
            LEFT JOIN usuarios u ON u.id = t.professor_id
            WHERE t.curso_id = :curso_id AND t.status IN ('aberta', 'em_andamento')
            ORDER BY t.data_inicio ASC
        ");
        $stmtTurmas->execute([':curso_id' => $cursoId]);
        $turmasRows = $stmtTurmas->fetchAll(PDO::FETCH_ASSOC);

        $turmas = [];
        foreach ($turmasRows as $turma) {
            $maxVagas = (int) ($turma['max_vagas'] ?: 30);
            $vagasOcupadas = (int) $turma['vagas_ocupadas'];
            $vagasDisponiveis = max(0, $maxVagas - $vagasOcupadas);

            $turmas[] = [
                'id' => (int) $turma['id'],
                'nome' => $turma['nome'],
                'professor' => $turma['professor_nome'],
                'horario_inicio' => $turma['horario_inicio'],
                'horario_fim' => $turma['horario_fim'],
                'horario_formatado' => substr($turma['horario_inicio'], 0, 5) . ' às ' . substr($turma['horario_fim'], 0, 5),
                'data_inicio' => $turma['data_inicio'],
                'data_fim' => $turma['data_fim'],
                'dias_semana' => $turma['dias_semana'],
                'local_aula' => $turma['local_aula'],
                'max_vagas' => $maxVagas,
                'vagas_ocupadas' => $vagasOcupadas,
                'vagas_disponiveis' => $vagasDisponiveis,
                'percentual_ocupacao' => $maxVagas > 0 ? round(($vagasOcupadas / $maxVagas) * 100, 1) : 0,
                'tem_vagas' => $vagasDisponiveis > 0,
                'status' => $turma['status'],
            ];
        }

        $stmtInscricoes = $pdo->prepare("
            SELECT COUNT(*) FROM lhs_inscricoes WHERE curso_id = :curso_id AND status = 'pendente'
        ");
        $stmtInscricoes->execute([':curso_id' => $cursoId]);
        $inscricoesPendentes = (int) $stmtInscricoes->fetchColumn();

        $temTurmasAbertas = count(array_filter($turmas, fn($t) => $t['tem_vagas'])) > 0;

        $cursos[] = [
            'id' => $cursoId,
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
            'turmas' => $turmas,
            'total_turmas_abertas' => count($turmas),
            'inscricoes_pendentes' => $inscricoesPendentes,
            'aceita_inscricoes' => $temTurmasAbertas,
        ];
    }

    $stmtAlunosFormados = $pdo->query("
        SELECT COUNT(DISTINCT ta.aluno_id) 
        FROM lhs_turma_alunos ta 
        WHERE ta.status = 'aprovado'
    ");
    $alunosFormados = (int) $stmtAlunosFormados->fetchColumn();

    $stmtAlunosAtivos = $pdo->query("SELECT COUNT(*) FROM lhs_alunos WHERE ativo = 1");
    $alunosAtivos = (int) $stmtAlunosAtivos->fetchColumn();

    $stmtCursosAtivos = $pdo->query("SELECT COUNT(*) FROM lhs_cursos WHERE ativo = 1");
    $cursosAtivos = (int) $stmtCursosAtivos->fetchColumn();

    $stmtTurmasAbertas = $pdo->query("SELECT COUNT(*) FROM lhs_turmas WHERE status IN ('aberta', 'em_andamento')");
    $turmasAbertas = (int) $stmtTurmasAbertas->fetchColumn();

    $stmtCertificados = $pdo->query("SELECT COUNT(*) FROM lhs_certificados");
    $certificadosEmitidos = (int) $stmtCertificados->fetchColumn();

    $stmtPresenca = $pdo->query("
        SELECT ROUND(
            (SELECT COUNT(*) FROM lhs_presencas WHERE presente = 1) * 100.0 / 
            NULLIF((SELECT COUNT(*) FROM lhs_presencas), 0)
        , 0) as media
    ");
    $presencaMedia = (int) ($stmtPresenca->fetchColumn() ?? 0);

    $estatisticas = [
        'alunos_formados' => $alunosFormados,
        'alunos_ativos' => $alunosAtivos,
        'cursos_disponiveis' => $cursosAtivos,
        'turmas_abertas' => $turmasAbertas,
        'certificados_emitidos' => $certificadosEmitidos,
        'taxa_presenca' => $presencaMedia,
        'taxa_presenca_formatada' => $presencaMedia . '%',
    ];

    $stmtFaq = $pdo->query("
        SELECT id, pergunta, resposta 
        FROM lhs_faq 
        WHERE ativo = 1 
        ORDER BY ordem ASC
    ");
    $faq = $stmtFaq->fetchAll(PDO::FETCH_ASSOC);
    $faq = array_map(function ($item) {
        return [
            'id' => (int) $item['id'],
            'pergunta' => $item['pergunta'],
            'resposta' => $item['resposta'],
        ];
    }, $faq);

    $stmtDepoimentos = $pdo->query("
        SELECT id, nome, curso_nome, texto, nota 
        FROM lhs_depoimentos 
        WHERE aprovado = 1 
        ORDER BY nota DESC, criado_em DESC 
        LIMIT 6
    ");
    $depoimentos = $stmtDepoimentos->fetchAll(PDO::FETCH_ASSOC);
    $depoimentos = array_map(function ($item) {
        return [
            'id' => (int) $item['id'],
            'nome' => $item['nome'],
            'curso' => $item['curso_nome'],
            'texto' => $item['texto'],
            'nota' => (int) $item['nota'],
        ];
    }, $depoimentos);

    $contato = [
        'titulo' => 'Entre em contato',
        'descricao' => 'Tem dúvidas ou precisa de mais informações? Fale conosco!',
        'endereco' => 'Lan House Social - SECTI',
        'horario_funcionamento' => 'Segunda a Sexta, das 08:00 às 21:00',
    ];

    $response = [
        'programa' => $programa,
        'cursos' => $cursos,
        'estatisticas' => $estatisticas,
        'depoimentos' => $depoimentos,
        'faq' => $faq,
        'contato' => $contato,
        'inscricoes_abertas' => count(array_filter($cursos, fn($c) => $c['aceita_inscricoes'])) > 0,
        'atualizado_em' => date('Y-m-d\TH:i:s'),
    ];

    json($response);
} catch (PDOException $e) {
    json(['error' => 'Erro ao carregar dados da landing page.'], 500);
    exit;
}
