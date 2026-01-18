<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../lib/db.php';

cors();

try {
  $pdo = db();
  $r = $pdo->query("SELECT 1 AS ok")->fetch();
  json(['db' => 'ok', 'res' => $r]);
} catch (Throwable $e) {
  json(['db' => 'fail', 'error' => $e->getMessage()], 500);
}
