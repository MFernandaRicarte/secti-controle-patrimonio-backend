<?php

require __DIR__ . '/../../lib/http.php';

cors();

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

if ($method !== 'GET') {
    http_response_code(405);
    echo json_encode([
        'error' => 'Método não permitido. Use GET.',
    ]);
    exit;
}

$dsn  = 'mysql:host=127.0.0.1;port=3306;dbname=secti;charset=utf8mb4';
$user = 'secti';
$pass = 'secti';

try {
    $pdo = new PDO($dsn, $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao conectar ao banco de dados.',
    ]);
    exit;
}

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$where = '';
$params = [];

if ($q !== '') {
    $where = "WHERE (
        l.numero LIKE :q
        OR l.objeto LIKE :q
        OR l.modalidade LIKE :q
        OR COALESCE(s.nome, '') LIKE :q
    )";
    $params[':q'] = '%' . $q . '%';
}

$sql = "
SELECT
    l.id,
    l.numero,
    l.modalidade,
    l.objeto,
    
    l.secretaria_id,
    s.nome AS secretaria,
    
    l.data_abertura,
    l.valor_estimado,
    l.status,
    l.criado_em,
    l.atualizado_em
FROM licitacoes l
LEFT JOIN setores s ON s.id = l.secretaria_id
{$where}
ORDER BY l.data_abertura DESC, l.criado_em DESC, l.id DESC
LIMIT 200
";

try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if (empty($rows)) {
        echo json_encode([
            'message' => 'Não há licitações cadastradas.',
            'data' => []
        ]);
        exit;
    }

    $licitacoes = array_map(function ($row) {
        return [
            'id'             => (int) $row['id'],
            'numero'         => $row['numero'],
            'modalidade'     => $row['modalidade'],
            'objeto'         => $row['objeto'],
            'secretaria_id'  => $row['secretaria_id'] ? (int)$row['secretaria_id'] : null,
            'secretaria'     => $row['secretaria'],
            'data_abertura'  => $row['data_abertura'],
            'valor_estimado' => $row['valor_estimado'],
            'status'         => $row['status'],
            'criado_em'      => $row['criado_em'],
            'atualizado_em'  => $row['atualizado_em'],
        ];
    }, $rows);

    echo json_encode($licitacoes);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'error' => 'Erro ao buscar licitações.',
    ]);
}