<?php
require_once __DIR__ . '/../../lib/http.php';
require_once __DIR__ . '/../../lib/auth.php';
require_once __DIR__ . '/../../lib/cors.php';

cors();
$usuario = requireAuth();

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json(['sucesso' => false, 'error' => 'Método não permitido'], 405);
}

$input = json_decode(file_get_contents('php://input'), true) ?? [];

$nome = trim($input['nome'] ?? '');
$cnpj = preg_replace('/\D+/', '', (string)($input['cnpj'] ?? ''));
$email = trim($input['email'] ?? '');
$telefone = trim($input['telefone'] ?? '');

if ($nome === '') json(['sucesso' => false, 'error' => 'Nome é obrigatório'], 422);
if ($cnpj === '') json(['sucesso' => false, 'error' => 'CNPJ é obrigatório'], 422);

try {
    $pdo = db();

    $st = $pdo->prepare("SELECT id FROM fornecedores WHERE cnpj = ? LIMIT 1");
    $st->execute([$cnpj]);
    if ($st->fetchColumn()) {
        json(['sucesso' => false, 'error' => 'CNPJ já cadastrado'], 409);
    }

    $ins = $pdo->prepare("
        INSERT INTO fornecedores (nome, cnpj, email, telefone)
        VALUES (?, ?, ?, ?)
    ");
    $ins->execute([
        $nome,
        $cnpj,
        $email !== '' ? $email : null,
        $telefone !== '' ? $telefone : null,
    ]);

    $id = (int)$pdo->lastInsertId();

    json([
        'sucesso' => true,
        'mensagem' => 'Fornecedor criado com sucesso',
        'id' => $id,
    ], 201);

} catch (PDOException $e) {
    json(['sucesso' => false, 'error' => 'Erro ao criar fornecedor'], 500);
}