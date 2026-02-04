<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
    exit;
}

$id = isset($GLOBALS['routeParams']['id']) ? (int) $GLOBALS['routeParams']['id'] : 0;

if ($id <= 0) {
    json(['error' => 'ID inválido'], 400);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$stmt = $pdo->prepare("
    SELECT 
        c.id,
        c.numero,
        c.ano_contrato,
        c.licitacao_id,
        l.numero AS licitacao_numero,
        c.fornecedor_id,
        f.nome AS fornecedor_nome,
        f.cnpj AS fornecedor_cnpj,
        c.objeto,
        c.data_inicio,
        c.data_fim,
        c.valor_contratado,
        c.valor_executado,
        c.valor_saldo,
        c.status,
        c.observacoes,
        c.criado_por,
        u_criado.nome AS criado_por_nome,
        c.criado_em,
        c.atualizado_por,
        u_atualizado.nome AS atualizado_por_nome,
        c.atualizado_em
    FROM contratos c
    LEFT JOIN fornecedores f ON f.id = c.fornecedor_id
    LEFT JOIN licitacoes l ON l.id = c.licitacao_id
    LEFT JOIN usuarios u_criado ON u_criado.id = c.criado_por
    LEFT JOIN usuarios u_atualizado ON u_atualizado.id = c.atualizado_por
    WHERE c.id = ?
");

$stmt->execute([$id]);
$row = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$row) {
    json(['error' => 'Contrato não encontrado'], 404);
    exit;
}

// Buscar aditivos
$sqlAditivos = "
    SELECT id, numero_aditivo, tipo, data_inicio, data_fim, valor_adicional, status, criado_em
    FROM contratos_aditivos
    WHERE contrato_id = ?
    ORDER BY criado_em DESC
";
$stmt = $pdo->prepare($sqlAditivos);
$stmt->execute([$id]);
$aditivos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar apostilamentos
$sqlApostilamentos = "
    SELECT id, numero_apostilamento, descricao, data_apostilamento, criado_em
    FROM contratos_apostilamentos
    WHERE contrato_id = ?
    ORDER BY data_apostilamento DESC
";
$stmt = $pdo->prepare($sqlApostilamentos);
$stmt->execute([$id]);
$apostilamentos = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar fiscais ativos
$sqlFiscais = "
    SELECT cf.id, u.nome, cf.data_nomeacao, cf.data_termino, cf.portaria_numero, cf.ativo
    FROM contratos_fiscais cf
    LEFT JOIN usuarios u ON u.id = cf.usuario_id
    WHERE cf.contrato_id = ? AND cf.ativo = TRUE
    ORDER BY cf.data_nomeacao DESC
";
$stmt = $pdo->prepare($sqlFiscais);
$stmt->execute([$id]);
$fiscais = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar gestores ativos
$sqlGestores = "
    SELECT cg.id, u.nome, cg.data_nomeacao, cg.data_termino, cg.portaria_numero, cg.ativo
    FROM contratos_gestores cg
    LEFT JOIN usuarios u ON u.id = cg.usuario_id
    WHERE cg.contrato_id = ? AND cg.ativo = TRUE
    ORDER BY cg.data_nomeacao DESC
";
$stmt = $pdo->prepare($sqlGestores);
$stmt->execute([$id]);
$gestores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Buscar empenhos vinculados
$sqlEmpenhos = "
    SELECT id, numero, valor_empenhado, valor_liquidado, valor_pago, status, criado_em
    FROM empenhos
    WHERE contrato_id = ?
    ORDER BY data_empenho DESC
";
$stmt = $pdo->prepare($sqlEmpenhos);
$stmt->execute([$id]);
$empenhos = $stmt->fetchAll(PDO::FETCH_ASSOC);

$contrato = [
    'id' => (int) $row['id'],
    'numero' => $row['numero'],
    'ano_contrato' => (int) $row['ano_contrato'],
    'licitacao_id' => $row['licitacao_id'] ? (int) $row['licitacao_id'] : null,
    'licitacao_numero' => $row['licitacao_numero'],
    'fornecedor_id' => (int) $row['fornecedor_id'],
    'fornecedor_nome' => $row['fornecedor_nome'],
    'fornecedor_cnpj' => $row['fornecedor_cnpj'],
    'objeto' => $row['objeto'],
    'data_inicio' => $row['data_inicio'],
    'data_fim' => $row['data_fim'],
    'valor_contratado' => (float) $row['valor_contratado'],
    'valor_executado' => (float) $row['valor_executado'],
    'valor_saldo' => (float) $row['valor_saldo'],
    'status' => $row['status'],
    'observacoes' => $row['observacoes'],
    'criado_por' => $row['criado_por'] ? (int) $row['criado_por'] : null,
    'criado_por_nome' => $row['criado_por_nome'],
    'criado_em' => $row['criado_em'],
    'atualizado_por' => $row['atualizado_por'] ? (int) $row['atualizado_por'] : null,
    'atualizado_por_nome' => $row['atualizado_por_nome'],
    'atualizado_em' => $row['atualizado_em'],
    'aditivos' => array_map(function ($a) {
        return [
            'id' => (int) $a['id'],
            'numero_aditivo' => $a['numero_aditivo'],
            'tipo' => $a['tipo'],
            'data_inicio' => $a['data_inicio'],
            'data_fim' => $a['data_fim'],
            'valor_adicional' => (float) $a['valor_adicional'],
            'status' => $a['status'],
            'criado_em' => $a['criado_em'],
        ];
    }, $aditivos),
    'apostilamentos' => array_map(function ($a) {
        return [
            'id' => (int) $a['id'],
            'numero_apostilamento' => $a['numero_apostilamento'],
            'descricao' => $a['descricao'],
            'data_apostilamento' => $a['data_apostilamento'],
            'criado_em' => $a['criado_em'],
        ];
    }, $apostilamentos),
    'fiscais' => array_map(function ($f) {
        return [
            'id' => (int) $f['id'],
            'nome' => $f['nome'],
            'data_nomeacao' => $f['data_nomeacao'],
            'data_termino' => $f['data_termino'],
            'portaria_numero' => $f['portaria_numero'],
            'ativo' => (bool) $f['ativo'],
        ];
    }, $fiscais),
    'gestores' => array_map(function ($g) {
        return [
            'id' => (int) $g['id'],
            'nome' => $g['nome'],
            'data_nomeacao' => $g['data_nomeacao'],
            'data_termino' => $g['data_termino'],
            'portaria_numero' => $g['portaria_numero'],
            'ativo' => (bool) $g['ativo'],
        ];
    }, $gestores),
    'empenhos' => array_map(function ($e) {
        return [
            'id' => (int) $e['id'],
            'numero' => $e['numero'],
            'valor_empenhado' => (float) $e['valor_empenhado'],
            'valor_liquidado' => (float) $e['valor_liquidado'],
            'valor_pago' => (float) $e['valor_pago'],
            'status' => $e['status'],
            'criado_em' => $e['criado_em'],
        ];
    }, $empenhos),
];

json($contrato);