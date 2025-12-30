<?php
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/db.php';

header('Content-Type: application/json; charset=utf-8');

$pdo = db();

$sql = "
  SELECT
    COALESCE(s.nome, 'Não informado') AS sala,
    COUNT(*) AS total
  FROM bens_patrimoniais b
  LEFT JOIN salas s ON s.id = b.sala_id
  WHERE b.tipo_eletronico = 'Notebook'
  GROUP BY sala
  ORDER BY total DESC
  LIMIT 15
";

$stmt = $pdo->query($sql);
echo json_encode($stmt->fetchAll(PDO::FETCH_ASSOC));