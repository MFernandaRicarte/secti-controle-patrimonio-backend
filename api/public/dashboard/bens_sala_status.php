<?php
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

$sql = "
  SELECT
    s.nome AS sala,
    COALESCE(NULLIF(TRIM(b.estado), ''), 'Não informado') AS status,
    COUNT(*) AS total
  FROM bens_patrimoniais b
  LEFT JOIN salas s ON s.id = b.sala_id
  GROUP BY s.nome, b.estado
";

$stmt = $pdo->query($sql);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$map = [];
foreach ($rows as $r) {
  $sala = $r['sala'] ?? 'Não informado';
  $status = strtoupper(str_replace(' ', '_', $r['status']));

  if (!isset($map[$sala])) {
    $map[$sala] = ['sala' => $sala];
  }

  $map[$sala][$status] = (int)$r['total'];
}

echo json_encode(array_values($map));