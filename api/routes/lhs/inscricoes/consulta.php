<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$protocolo = trim($_GET['protocolo'] ?? '');

if ($protocolo === '') {
    json(['error' => 'Número de protocolo é obrigatório.'], 400);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT 
        i.id,
        i.numero_protocolo,
        i.nome,
        i.status,
        i.motivo_rejeicao,
        i.criado_em,
        i.atualizado_em,
        c.nome AS curso_nome,
        t.nome AS turma_nome,
        t.horario_inicio,
        t.horario_fim,
        t.data_inicio
    FROM lhs_inscricoes i
    LEFT JOIN lhs_cursos c ON c.id = i.curso_id
    LEFT JOIN lhs_turmas t ON t.id = i.turma_preferencia_id
    WHERE i.numero_protocolo = ?
");
$stmt->execute([$protocolo]);
$inscricao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$inscricao) {
    json(['error' => 'Inscrição não encontrada com este protocolo.'], 404);
}

$response = [
    'numero_protocolo' => $inscricao['numero_protocolo'],
    'nome' => $inscricao['nome'],
    'status' => $inscricao['status'],
    'status_texto' => match($inscricao['status']) {
        'pendente' => 'Aguardando análise',
        'aprovado' => 'Aprovado',
        'rejeitado' => 'Não aprovado',
        default => $inscricao['status']
    },
    'curso_nome' => $inscricao['curso_nome'],
    'criado_em' => $inscricao['criado_em'],
    'atualizado_em' => $inscricao['atualizado_em'],
];

if ($inscricao['status'] === 'rejeitado' && $inscricao['motivo_rejeicao']) {
    $response['motivo_rejeicao'] = $inscricao['motivo_rejeicao'];
}

if ($inscricao['status'] === 'aprovado' && $inscricao['turma_nome']) {
    $response['turma'] = [
        'nome' => $inscricao['turma_nome'],
        'horario_inicio' => $inscricao['horario_inicio'],
        'horario_fim' => $inscricao['horario_fim'],
        'data_inicio' => $inscricao['data_inicio'],
    ];
}

json($response);
