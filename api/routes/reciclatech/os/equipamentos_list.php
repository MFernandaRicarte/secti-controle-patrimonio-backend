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

$osId = (int)($GLOBALS['routeParams']['id'] ?? 0);
if ($osId <= 0) {
    json(['error' => 'id inválido.'], 400);
}

$stmt = $pdo->prepare("
    SELECT
        e.id,
        e.os_id,
        e.tipo,
        e.descricao,
        e.numero_item,
        e.destino_padrao,
        e.destino_outro,
        e.destino_final,
        e.criado_em,
        e.atualizado_em
    FROM rct_os_equipamentos e
    WHERE e.os_id = ?
    ORDER BY e.tipo ASC, e.numero_item ASC, e.id ASC
");
$stmt->execute([$osId]);
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
        'criado_em' => $r['criado_em'],
        'atualizado_em' => $r['atualizado_em'],
    ];
}, $rows));