<?php

require __DIR__ . '/../../lib/http.php';
require __DIR__ . '/../../config/config.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/auth.php';

cors();

$usuario = requireAuth();
header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    json(['error' => 'Método não permitido. Use GET.']);
    exit;
}

try {
    $pdo = db();
} catch (PDOException $e) {
    http_response_code(500);
    json(['error' => 'Erro ao conectar ao banco de dados.']);
    exit;
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
        json(['sucesso' => false, 'message' => 'Não há contratos cadastrados.', 'dados' => [], 'total' => 0]);
        exit;
    }

    $contratos = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'numero' => $row['numero'],
            'ano_contrato' => (int) $row['ano_contrato'],
            'licitacao_id' => $row['licitacao_id'] ? (int) $row['licitacao_id'] : null,
            'fornecedor_id' => (int) $row['fornecedor_id'],
            'fornecedor_nome' => $row['fornecedor_nome'],
            'objeto' => $row['objeto'],
            'data_inicio' => $row['data_inicio'],
            'data_fim' => $row['data_fim'],
            'valor_contratado' => (float) $row['valor_contratado'],
            'valor_executado' => (float) $row['valor_executado'],
            'valor_saldo' => (float) $row['valor_saldo'],
            'status' => $row['status'],
            'criado_em' => $row['criado_em'],
            'atualizado_em' => $row['atualizado_em'],
        ];
    }, $rows);

    json(['sucesso' => true, 'dados' => $contratos, 'total' => count($contratos)]);
} catch (PDOException $e) {
    http_response_code(500);
    json(['error' => 'Erro ao buscar contratos.']);
}
