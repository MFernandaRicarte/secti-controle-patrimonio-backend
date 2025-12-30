<?php
require __DIR__ . '/../../lib/cors.php';

ini_set('display_errors', 1);
error_reporting(E_ALL);

header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/_range.php';

$pdo = db();
[$start, $end] = dashboard_get_range();

/**
 * Materiais por PRODUTO (nome)
 * - usa produto_base como "nome do produto"
 * - soma estoque_atual como "quantidade"
 * - filtra pelo período (criado_em)
 */
$sql = "
  SELECT
    COALESCE(NULLIF(TRIM(i.produto_base), ''), 'Não informado') AS produto,
    SUM(COALESCE(i.estoque_atual, 0)) AS total
  FROM itens_estoque i
  WHERE i.criado_em >= ? AND i.criado_em < ?
  GROUP BY produto
  ORDER BY total DESC
  LIMIT 12
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start, $end]);

$out = [];
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
  $out[] = [
    'produto' => $r['produto'],
    'total'   => (int)$r['total'],
  ];
}

echo json_encode($out);