<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../lib/auth.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

requireAdminOrSuperAdmin();
$pdo = db();

$q = trim($_GET['q'] ?? '');
$destino = trim($_GET['destino'] ?? '');
$limit = (int)($_GET['limit'] ?? 500);
if ($limit <= 0) $limit = 500;
if ($limit > 1000) $limit = 1000;

$where = [];
$params = [];

if ($q !== '') {
    $where[] = "(s.protocolo LIKE :q OR s.nome LIKE :q OR e.tipo LIKE :q OR e.destino_final LIKE :q)";
    $params[':q'] = "%{$q}%";
}

if ($destino !== '') {
    $where[] = "e.destino_padrao = :destino";
    $params[':destino'] = $destino;
}

$whereSql = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$sql = "
    SELECT
        e.id,
        e.os_id,
        e.tipo,
        e.descricao,
        e.numero_item,
        e.destino_padrao,
        e.destino_outro,
        e.destino_final,
        e.atualizado_em,
        o.status AS os_status,
        s.protocolo,
        s.nome,
        s.endereco
    FROM rct_os_equipamentos e
    JOIN rct_os o ON o.id = e.os_id
    JOIN rct_solicitacoes s ON s.id = o.solicitacao_id
    {$whereSql}
    ORDER BY e.atualizado_em DESC, e.id DESC
    LIMIT {$limit}
";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

json(array_map(function($r) {
    return [
        'id' => (int)$r['id'],
        'os_id' => (int)$r['os_id'],
        'tipo' => $r['tipo'],
        'descricao' => $r['descricao'],
        'numero_item' => (int)$r['numero_item'],
        'destino_padrao' => $r['destino_padrao'],
        'destino_outro' => $r['destino_outro'],
        'destino_final' => $r['destino_final'],
        'atualizado_em' => $r['atualizado_em'],
        'os_status' => $r['os_status'],
        'protocolo' => $r['protocolo'],
        'nome' => $r['nome'],
        'endereco' => $r['endereco'],
    ];
}, $rows));