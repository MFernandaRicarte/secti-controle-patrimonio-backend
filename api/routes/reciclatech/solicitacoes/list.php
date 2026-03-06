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

$sql = "
    SELECT
        s.id,
        s.protocolo,
        s.nome,
        s.endereco,
        s.status,
        s.criado_em,
        s.atualizado_em,
        (
            SELECT GROUP_CONCAT(CONCAT(i.quantidade, ' ', i.tipo) SEPARATOR ', ')
            FROM rct_solicitacao_itens i
            WHERE i.solicitacao_id = s.id
        ) AS itens_resumo,
        (
            SELECT COUNT(*)
            FROM rct_os o
            WHERE o.solicitacao_id = s.id
        ) AS tem_os,
        (
            SELECT o.id
            FROM rct_os o
            WHERE o.solicitacao_id = s.id
            ORDER BY o.id DESC
            LIMIT 1
        ) AS os_id
    FROM rct_solicitacoes s
    ORDER BY s.id DESC
    LIMIT 500
";

$rows = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

$resp = array_map(function($r) {
    return [
        'id' => (int)$r['id'],
        'protocolo' => $r['protocolo'],
        'nome' => $r['nome'],
        'endereco' => $r['endereco'],
        'status' => $r['status'],
        'criado_em' => $r['criado_em'],
        'atualizado_em' => $r['atualizado_em'],
        'itens_resumo' => $r['itens_resumo'] ?: '',
        'tem_os' => ((int)$r['tem_os']) > 0,
        'os_id' => $r['os_id'] ? (int)$r['os_id'] : null,
    ];
}, $rows);

json($resp);