<?php
require __DIR__ . '/../../../lib/db.php';
require __DIR__ . '/../../../lib/http.php';

cors();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    json(['error' => 'Método não permitido. Use GET.'], 405);
}

$pdo = db();

$rows = $pdo->query("
    SELECT id, nome, descricao, icone, exemplos, ordem
    FROM rct_categorias
    WHERE ativo = 1
    ORDER BY ordem ASC, nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

$categorias = array_map(function ($r) {
    return [
        'id'        => (int)$r['id'],
        'nome'      => $r['nome'],
        'descricao' => $r['descricao'],
        'icone'     => $r['icone'],
        'exemplos'  => $r['exemplos']
            ? array_map('trim', explode(',', $r['exemplos']))
            : [],
        'ordem'     => (int)$r['ordem'],
    ];
}, $rows);

json($categorias);
