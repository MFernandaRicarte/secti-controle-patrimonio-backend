<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$user = requireAdminOrSuperAdmin();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$status = trim($_GET['status'] ?? ''); // ex: ABERTA, EM_MANUTENCAO, FINALIZADA...
$limit = (int)($_GET['limit'] ?? 200);
if ($limit <= 0) $limit = 200;
if ($limit > 500) $limit = 500;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(s.protocolo LIKE :q OR s.nome LIKE :q OR s.endereco LIKE :q)";
    $params[':q'] = "%{$q}%";
}

if ($status !== '') {
    $where[] = "o.status = :status";
    $params[':status'] = $status;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        o.id,
        o.solicitacao_id,
        o.status,
        o.criado_em,
        s.protocolo,
        s.nome,
        s.endereco,
        (SELECT COUNT(*) FROM rct_os_itens i WHERE i.os_id = o.id) AS total_itens
    FROM rct_os o
    JOIN rct_solicitacoes s ON s.id = o.solicitacao_id
    {$whereSql}
    ORDER BY o.id DESC
    LIMIT {$limit}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$out = array_map(function ($r) {
    return [
        'id' => (int)$r['id'],
        'solicitacao_id' => (int)$r['solicitacao_id'],
        'status' => $r['status'],
        'criado_em' => $r['criado_em'],
        'protocolo' => $r['protocolo'],
        'nome' => $r['nome'],
        'endereco' => $r['endereco'],
        'total_itens' => (int)$r['total_itens'],
    ];
}, $rows);

json($out);