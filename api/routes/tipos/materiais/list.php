<?php
require __DIR__ . '/../../../lib/http.php';
require __DIR__ . '/../../../config/config.php';

cors();

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$pdo = db();

if ($method === 'GET') {
    $stmt = $pdo->query("
        SELECT id, nome, criado_em
        FROM tipos_materiais_consumo
        ORDER BY nome ASC
    ");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    json($rows);
}

if ($method === 'POST') {
    $input = json_decode(file_get_contents('php://input'), true) ?? [];
    $nome = trim($input['nome'] ?? '');

    if ($nome === '') {
        json(['error' => 'nome é obrigatório'], 422);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO tipos_materiais_consumo (nome)
            VALUES (?)
        ");
        $stmt->execute([$nome]);

        $id = (int)$pdo->lastInsertId();

        $stmt2 = $pdo->prepare("
            SELECT id, nome, criado_em
            FROM tipos_materiais_consumo
            WHERE id = ?
        ");
        $stmt2->execute([$id]);
        $row = $stmt2->fetch(PDO::FETCH_ASSOC);

        json($row, 201);
    } catch (PDOException $e) {
        if ($e->getCode() === '23000') {
            json(['error' => 'Já existe um tipo de material com esse nome'], 409);
        }
        json(['error' => 'Erro ao salvar tipo de material'], 500);
    }
}

json(['error' => 'Método não permitido'], 405);