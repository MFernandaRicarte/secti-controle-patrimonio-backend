<?php
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/db.php';
require __DIR__ . '/_range.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();
[$start, $end] = dashboard_get_range();

$sql = "
  SELECT
    i.descricao AS item,
    SUM(i.estoque_atual) AS total
  FROM itens_estoque i
  WHERE i.criado_em >= ? AND i.criado_em < ?
  GROUP BY item
  ORDER BY total DESC
  LIMIT 10
";

$stmt = $pdo->prepare($sql);
$stmt->execute([$start, $end]);

echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));