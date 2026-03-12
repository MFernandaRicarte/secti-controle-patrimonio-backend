<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$protocolo = trim($_GET['protocolo'] ?? '');

if ($protocolo === '') {
    json(['error' => 'O parâmetro protocolo é obrigatório.'], 422);
}

$pdo = db();

$stmt = $pdo->prepare("
    SELECT id, protocolo, nome, status, criado_em, atualizado_em
    FROM rct_solicitacoes
    WHERE protocolo = ?
    LIMIT 1
");
$stmt->execute([$protocolo]);
$sol = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$sol) {
    json(['error' => 'Solicitação não encontrada. Verifique o protocolo informado.'], 404);
}

$stmtItens = $pdo->prepare("
    SELECT tipo, quantidade, descricao
    FROM rct_solicitacao_itens
    WHERE solicitacao_id = ?
    ORDER BY id ASC
");
$stmtItens->execute([(int)$sol['id']]);
$itens = $stmtItens->fetchAll(PDO::FETCH_ASSOC);

$stmtOs = $pdo->prepare("
    SELECT id, status, criado_em
    FROM rct_os
    WHERE solicitacao_id = ?
    ORDER BY id DESC
    LIMIT 1
");
$stmtOs->execute([(int)$sol['id']]);
$os = $stmtOs->fetch(PDO::FETCH_ASSOC);

$statusLabels = [
    'ABERTA'    => 'Aguardando análise',
    'TRIAGEM'   => 'Em triagem',
    'AGENDADA'  => 'Coleta agendada',
    'COLETADA'  => 'Itens coletados',
    'CANCELADA' => 'Cancelada',
];

$osStatusLabels = [
    'ABERTA'         => 'Ordem de serviço aberta',
    'EM_TRIAGEM'     => 'Em triagem técnica',
    'EM_MANUTENCAO'  => 'Em manutenção/recondicionamento',
    'PARA_DESCARTE'  => 'Destinado ao descarte ecológico',
    'FINALIZADA'     => 'Processo finalizado',
    'CANCELADA'      => 'Cancelada',
];

$etapas = [
    ['etapa' => 'Solicitação recebida',   'concluida' => true, 'data' => $sol['criado_em']],
    ['etapa' => 'Em triagem',             'concluida' => in_array($sol['status'], ['TRIAGEM','AGENDADA','COLETADA']), 'data' => null],
    ['etapa' => 'Coleta agendada',        'concluida' => in_array($sol['status'], ['AGENDADA','COLETADA']), 'data' => null],
    ['etapa' => 'Itens coletados',        'concluida' => $sol['status'] === 'COLETADA', 'data' => null],
];

if ($os) {
    $etapas[] = ['etapa' => 'Ordem de serviço criada', 'concluida' => true, 'data' => $os['criado_em']];
    $etapas[] = ['etapa' => 'Processo finalizado',     'concluida' => ($os['status'] ?? '') === 'FINALIZADA', 'data' => null];
}

json([
    'protocolo'    => $sol['protocolo'],
    'nome'         => $sol['nome'],
    'status'       => $sol['status'],
    'status_label' => $statusLabels[$sol['status']] ?? $sol['status'],
    'criado_em'    => $sol['criado_em'],
    'atualizado_em'=> $sol['atualizado_em'],
    'itens'        => array_map(function ($r) {
        return [
            'tipo'       => $r['tipo'],
            'quantidade' => (int)$r['quantidade'],
            'descricao'  => $r['descricao'],
        ];
    }, $itens),
    'ordem_servico' => $os ? [
        'id'           => (int)$os['id'],
        'status'       => $os['status'],
        'status_label' => $osStatusLabels[$os['status']] ?? $os['status'],
        'criado_em'    => $os['criado_em'],
    ] : null,
    'etapas' => $etapas,
]);
