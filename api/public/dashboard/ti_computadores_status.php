<?php
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

$sql = "
  SELECT
    COALESCE(NULLIF(TRIM(estado), ''), 'Não informado') AS status,
    COUNT(*) AS total
  FROM bens_patrimoniais
  WHERE tipo_eletronico = 'Notebook'
  GROUP BY status
  ORDER BY total DESC
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));