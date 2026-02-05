<?php

function json($data, int $code = 200) {
  http_response_code($code);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode($data, JSON_UNESCAPED_UNICODE);
  exit;
}

function cors() {
  $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
  $allowed = ['http://localhost:5173', 'http://127.0.0.1:5173'];

  if (in_array($origin, $allowed, true)) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
  } else {
    header("Access-Control-Allow-Origin: *");
  }

  header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
  header('Access-Control-Allow-Headers: Content-Type, Authorization, X-User-Id');
  header('Access-Control-Max-Age: 86400');

  if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
  }
}