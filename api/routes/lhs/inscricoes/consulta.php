<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

$protocolo = trim($_GET['protocolo'] ?? '');

if ($protocolo === '') {
    json(['error' => 'Número de protocolo é obrigatório.'], 400);
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
        SELECT 
            i.id,
            i.numero_protocolo,
            i.nome,
            i.email,
            i.status,
            i.motivo_rejeicao,
            i.criado_em,
            i.atualizado_em,
            c.id AS curso_id,
            c.nome AS curso_nome,
            c.carga_horaria,
            c.ementa,
            c.descricao AS curso_descricao,
            t.id AS turma_id,
            t.nome AS turma_nome,
            t.horario_inicio,
            t.horario_fim,
            t.data_inicio,
            t.data_fim,
            t.local_aula,
            t.dias_semana,
            t.status AS turma_status,
            u.nome AS aprovado_por_nome,
            a.id AS aluno_id
        FROM lhs_inscricoes i
        LEFT JOIN lhs_cursos c ON c.id = i.curso_id
        LEFT JOIN lhs_turmas t ON t.id = i.turma_preferencia_id
        LEFT JOIN usuarios u ON u.id = i.aprovado_por
        LEFT JOIN lhs_alunos a ON a.id = i.aluno_id
        WHERE i.numero_protocolo = ?
    ");
    $stmt->execute([$protocolo]);
    $inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$inscricao) {
        json(['error' => 'Inscrição não encontrada com este protocolo.'], 404);
        exit;
    }

    $statusConfig = match($inscricao['status']) {
        'pendente' => [
            'texto' => 'Aguardando Análise',
            'cor' => '#F59E0B',
            'icone' => 'Clock',
            'descricao' => 'Sua inscrição foi recebida e está sendo analisada pela equipe. Você será notificado assim que houver uma atualização.',
        ],
        'aprovado' => [
            'texto' => 'Aprovado',
            'cor' => '#10B981',
            'icone' => 'CheckCircle',
            'descricao' => 'Parabéns! Sua inscrição foi aprovada. Confira abaixo os detalhes da sua turma.',
        ],
        'rejeitado' => [
            'texto' => 'Não Aprovado',
            'cor' => '#EF4444',
            'icone' => 'XCircle',
            'descricao' => 'Infelizmente sua inscrição não foi aprovada neste momento. Confira o motivo abaixo.',
        ],
        default => [
            'texto' => $inscricao['status'],
            'cor' => '#6B7280',
            'icone' => 'Info',
            'descricao' => '',
        ],
    };

    $timeline = [];
    $timeline[] = [
        'etapa' => 'Inscrição Realizada',
        'data' => $inscricao['criado_em'],
        'concluida' => true,
        'icone' => 'FileText',
    ];
    $timeline[] = [
        'etapa' => 'Análise da Equipe',
        'data' => $inscricao['status'] !== 'pendente' ? $inscricao['atualizado_em'] : null,
        'concluida' => $inscricao['status'] !== 'pendente',
        'em_andamento' => $inscricao['status'] === 'pendente',
        'icone' => 'Search',
    ];

    if ($inscricao['status'] === 'aprovado') {
        $timeline[] = [
            'etapa' => 'Inscrição Aprovada',
            'data' => $inscricao['atualizado_em'],
            'concluida' => true,
            'icone' => 'CheckCircle',
        ];
        $timeline[] = [
            'etapa' => 'Matrícula Realizada',
            'data' => $inscricao['atualizado_em'],
            'concluida' => true,
            'icone' => 'UserCheck',
        ];
    } elseif ($inscricao['status'] === 'rejeitado') {
        $timeline[] = [
            'etapa' => 'Inscrição Não Aprovada',
            'data' => $inscricao['atualizado_em'],
            'concluida' => true,
            'icone' => 'XCircle',
        ];
    } else {
        $timeline[] = [
            'etapa' => 'Resultado',
            'data' => null,
            'concluida' => false,
            'icone' => 'Flag',
        ];
    }

    $response = [
        'numero_protocolo' => $inscricao['numero_protocolo'],
        'nome' => $inscricao['nome'],
        'status' => $inscricao['status'],
        'status_info' => $statusConfig,
        'curso' => [
            'id' => (int) $inscricao['curso_id'],
            'nome' => $inscricao['curso_nome'],
            'carga_horaria' => (int) $inscricao['carga_horaria'],
            'carga_horaria_formatada' => (int) $inscricao['carga_horaria'] . 'h',
            'descricao' => $inscricao['curso_descricao'],
        ],
        'timeline' => $timeline,
        'criado_em' => $inscricao['criado_em'],
        'atualizado_em' => $inscricao['atualizado_em'],
    ];

    if ($inscricao['status'] === 'rejeitado' && $inscricao['motivo_rejeicao']) {
        $response['motivo_rejeicao'] = $inscricao['motivo_rejeicao'];
        $response['proximos_passos'] = [
            'Você pode realizar uma nova inscrição a qualquer momento.',
            'Caso tenha dúvidas, entre em contato com a equipe do Lan House Social.',
            'Fique atento às novas turmas que serão abertas em breve!',
        ];
    }

    if ($inscricao['status'] === 'aprovado' && $inscricao['turma_nome']) {
        $response['turma'] = [
            'id' => (int) $inscricao['turma_id'],
            'nome' => $inscricao['turma_nome'],
            'horario_inicio' => $inscricao['horario_inicio'],
            'horario_fim' => $inscricao['horario_fim'],
            'horario_formatado' => substr($inscricao['horario_inicio'], 0, 5) . ' às ' . substr($inscricao['horario_fim'], 0, 5),
            'data_inicio' => $inscricao['data_inicio'],
            'data_fim' => $inscricao['data_fim'],
            'dias_semana' => $inscricao['dias_semana'],
            'local_aula' => $inscricao['local_aula'],
            'status' => $inscricao['turma_status'],
        ];
        $response['proximos_passos'] = [
            'Compareça no primeiro dia de aula no local e horário indicados.',
            'Leve um documento de identificação com foto.',
            'Mantenha uma frequência mínima de 75% para obter o certificado.',
            'Em caso de dúvidas, procure a equipe do Lan House Social.',
        ];

        $stmtCert = $pdo->prepare("
            SELECT codigo_validacao, frequencia_final, emitido_em
            FROM lhs_certificados
            WHERE aluno_id = ? AND turma_id = ?
        ");
        $stmtCert->execute([$inscricao['aluno_id'], $inscricao['turma_id']]);
        $certificado = $stmtCert->fetch(PDO::FETCH_ASSOC);

        if ($certificado) {
            $response['certificado'] = [
                'codigo_validacao' => $certificado['codigo_validacao'],
                'frequencia_final' => (float) $certificado['frequencia_final'],
                'emitido_em' => $certificado['emitido_em'],
                'disponivel' => true,
            ];
        }
    }

    if ($inscricao['status'] === 'pendente') {
        $response['proximos_passos'] = [
            'Sua inscrição está na fila de análise.',
            'Guarde seu número de protocolo para futuras consultas.',
            'Você pode verificar o status a qualquer momento nesta página.',
            'A equipe entrará em contato caso precise de informações adicionais.',
        ];
    }

    json($response);
} catch (PDOException $e) {
    json(['error' => 'Erro ao consultar inscrição.'], 500);
    exit;
}
