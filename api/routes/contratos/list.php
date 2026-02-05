<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    json(['sucesso' => false, 'error' => 'Método não permitido. Use GET.'], 405);
}

try {
    $pdo = db();
} catch (PDOException $e) {
    json(['sucesso' => false, 'error' => 'Erro ao conectar ao banco de dados.'], 500);
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$fornecedor = isset($_GET['fornecedor']) ? (int) $_GET['fornecedor'] : 0;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(c.numero LIKE :q OR c.objeto LIKE :q OR f.nome LIKE :q)";
    $params[':q'] = '%' . $q . '%';
}

if ($status !== '') {
    $where[] = "c.status = :status";
    $params[':status'] = $status;
}

if ($fornecedor > 0) {
    $where[] = "c.fornecedor_id = :fornecedor";
    $params[':fornecedor'] = $fornecedor;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    c.id,
    c.numero,
    c.ano_contrato,
    c.licitacao_id,
    c.fornecedor_id,
    f.nome AS fornecedor_nome,
    c.objeto,
    c.data_inicio,
    c.data_fim,
    c.valor_contratado,
    c.valor_executado,
    c.valor_saldo,
    c.status,
    c.criado_em,
    c.atualizado_em
FROM contratos c
LEFT JOIN fornecedores f ON f.id = c.fornecedor_id
{$whereClause}
ORDER BY c.data_fim DESC, c.criado_em DESC
LIMIT 200
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        json(['sucesso' => true, 'dados' => [], 'total' => 0]);
    }

    $contratos = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'numero' => $row['numero'],
            'ano_contrato' => (int) $row['ano_contrato'],
            'licitacao_id' => $row['licitacao_id'] ? (int) $row['licitacao_id'] : null,
            'fornecedor_id' => $row['fornecedor_id'] ? (int) $row['fornecedor_id'] : null,
            'fornecedor_nome' => $row['fornecedor_nome'],
            'objeto' => $row['objeto'],
            'data_inicio' => $row['data_inicio'],
            'data_fim' => $row['data_fim'],
            'valor_contratado' => $row['valor_contratado'] !== null ? (float) $row['valor_contratado'] : 0.0,
            'valor_executado' => $row['valor_executado'] !== null ? (float) $row['valor_executado'] : 0.0,
            'valor_saldo' => $row['valor_saldo'] !== null ? (float) $row['valor_saldo'] : 0.0,
            'status' => $row['status'],
            'criado_em' => $row['criado_em'],
            'atualizado_em' => $row['atualizado_em'],
        ];
    }, $rows);

    json(['sucesso' => true, 'dados' => $contratos, 'total' => count($contratos)]);
} catch (PDOException $e) {
    json(['sucesso' => false, 'error' => 'Erro ao buscar contratos.'], 500);
}