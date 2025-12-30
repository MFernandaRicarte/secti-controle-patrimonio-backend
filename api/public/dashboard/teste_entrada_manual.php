<?php
require __DIR__ . '/../../lib/db.php';
$pdo = db();

// pegue um item existente
$itemId = 1; // ajuste para um ID real

$pdo->prepare("
  INSERT INTO entradas_estoque (item_id, quantidade, data_entrada)
  VALUES (?, 10, NOW())
")->execute([$itemId]);

$pdo->prepare("
  UPDATE itens_estoque
  SET estoque_atual = estoque_atual + 10
  WHERE id = ?
")->execute([$itemId]);

echo json_encode(["ok" => true]);