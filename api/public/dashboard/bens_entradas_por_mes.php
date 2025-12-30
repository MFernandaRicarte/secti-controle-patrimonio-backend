<?php
require __DIR__ . '/../../lib/cors.php';
require __DIR__ . '/../../lib/db.php';

header('Content-Type: application/json; charset=utf-8');

/* TESTE 1: db() existe? */
if (!function_exists('db')) {
  echo json_encode(["erro" => "funcao db() nao existe"]);
  exit;
}

/* TESTE 2: criar PDO */
try {
  $pdo = db();
} catch (Throwable $e) {
  echo json_encode([
    "erro" => "falha ao criar pdo",
    "msg" => $e->getMessage()
  ]);
  exit;
}

/* TESTE 3: confirmar execução */
echo json_encode([
  "ok" => true,
  "arquivo" => __FILE__,
  "pdo" => get_class($pdo)
]);
