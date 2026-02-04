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

$tipo = isset($_GET['tipo']) ? trim($_GET['tipo']) : '';
$status = isset($_GET['status']) ? trim($_GET['status']) : '';
$ano = isset($_GET['ano']) ? (int) $_GET['ano'] : 0;

$where = [];
$params = [];

if ($tipo !== '' && in_array($tipo, ['dispensa', 'inexigibilidade'], true)) {
    $where[] = "d.tipo = :tipo";
    $params[':tipo'] = $tipo;
}

if ($status !== '') {
    $where[] = "d.status = :status";
    $params[':status'] = $status;
}

if ($ano > 0) {
    $where[] = "d.ano = :ano";
    $params[':ano'] = $ano;
}

$whereClause = !empty($where) ? 'WHERE ' . implode(' AND ', $where) : '';

$sql = "
SELECT
    d.id,
    d.numero,
    d.ano,
    d.tipo,
    d.fornecedor_id,
    f.nome AS fornecedor_nome,
    d.objeto,
    d.valor,
    d.status,
    d.data_solicitacao,
    d.criado_em,
    d.atualizado_em
FROM dispensas_inexigibilidades d
LEFT JOIN fornecedores f ON f.id = d.fornecedor_id
{$whereClause}
ORDER BY d.data_solicitacao DESC
LIMIT 200
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        json(['message' => 'Não há dispensas/inexigibilidades cadastradas.', 'data' => []]);
        exit;
    }

    $dispensas = array_map(function ($row) {
        return [
            'id' => (int) $row['id'],
            'numero' => $row['numero'],
            'ano' => (int) $row['ano'],
            'tipo' => $row['tipo'],
            'fornecedor_id' => (int) $row['fornecedor_id'],
            'fornecedor_nome' => $row['fornecedor_nome'],
            'objeto' => $row['objeto'],
            'valor' => (float) $row['valor'],
            'status' => $row['status'],
            'data_solicitacao' => $row['data_solicitacao'],
            'criado_em' => $row['criado_em'],
            'atualizado_em' => $row['atualizado_em'],
        ];
    }, $rows);

    json($dispensas);
} catch (PDOException $e) {
    http_response_code(500);
    json(['error' => 'Erro ao buscar dispensas/inexigibilidades.']);
}
