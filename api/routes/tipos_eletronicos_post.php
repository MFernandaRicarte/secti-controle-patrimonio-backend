<?php
require_once __DIR__ . '/../lib/http.php';
require_once __DIR__ . '/../config/config.php';

cors();
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['error' => 'Método não permitido'], 405);
}

$data = json_decode(file_get_contents('php://input'), true);
$nome = trim($data['nome'] ?? '');

if ($nome === '') {
    json(['error' => 'nome é obrigatório'], 422);
}

try {
    $pdo = db();

    $stmt = $pdo->prepare("
        INSERT INTO tipos_eletronicos (nome)
        VALUES (?)
    ");
    $stmt->execute([$nome]);

    $id = (int)$pdo->lastInsertId();

    json(['id' => $id, 'nome' => $nome], 201);
} catch (PDOException $e) {
    if ($e->getCode() === '23000') {
        json(['error' => 'Já existe um tipo de eletrônico com esse nome'], 409);
    }
    json(['error' => 'Erro ao salvar tipo de eletrônico'], 500);
}