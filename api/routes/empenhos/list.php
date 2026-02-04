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

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['error' => 'Erro ao conectar ao banco.'], 500);
    exit;
}

$contrato = isset($_GET['contrato_id']) ? (int) $_GET['contrato_id'] : 0;
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : 0;

$where = [];
$params = [];

if ($contrato > 0) {
    $where[] = "e.contrato_id = :contrato_id";
    $params[':contrato_id'] = $contrato;
}

if ($status !== '') {
    $where[] = "e.status = :status";
    $params[':status'] = $status;
}

if ($ano > 0) {
    $where[] = "e.ano_empenho = :ano";
    $params[':ano'] = $ano;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    e.id,
    e.numero,
    e.ano_empenho,
    e.contrato_id,
    e.licitacao_id,
    e.valor_empenhado,
    e.valor_liquidado,
    e.valor_pago,
    e.saldo,
    e.status,
    e.descricao,
    e.data_empenho,
    e.criado_em,
    e.atualizado_em
FROM empenhos e
{$whereClause}
ORDER BY e.data_empenho DESC
LIMIT 200
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $empenhos = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'numero' => $row['numero'],
            'ano_empenho' => (int) $row['ano_empenho'],
            'contrato_id' => $row['contrato_id'] ? (int) $row['contrato_id'] : null,
            'licitacao_id' => $row['licitacao_id'] ? (int) $row['licitacao_id'] : null,
            'valor_empenhado' => (float) $row['valor_empenhado'],
            'valor_liquidado' => (float) $row['valor_liquidado'],
            'valor_pago' => (float) $row['valor_pago'],
            'saldo' => (float) $row['saldo'],
            'status' => $row['status'],
            'descricao' => $row['descricao'],
            'data_empenho' => $row['data_empenho'],
            'criado_em' => $row['criado_em'],
            'atualizado_em' => $row['atualizado_em'],
        ];
    }, $rows);

    json($empenhos);
} catch (PDOException $e) {
    http_response_code(500);
    json(['error' => 'Erro ao buscar empenhos.']);
}
